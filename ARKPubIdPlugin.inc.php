<?php

/**
 * @file plugins/pubIds/ark/ARKPubIdPlugin.inc.php
 *
 * @brief ARK PubId plugin for OJS 3.5.x - Supports articles and issues
 * 
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 */

use PKP\plugins\PKPPubIdPlugin;
use PKP\config\Config;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;
use APP\facades\Repo;
use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\components\forms\FieldText;

class ARKPubIdPlugin extends PKPPubIdPlugin
{
    private static $registered = false;
    
    // Validation server endpoint (only for NAAN validation)
    private const VALIDATION_SERVER_URL = 'https://revistacarnaubais.com.br/ark-telemetry/validate.php';
    private const STATISTICS_COLLECT_URL = 'https://revistacarnaubais.com.br/ark-telemetry/collect.php';
    
    /**
     * Get plugin version from version.xml
     * 
     * @return string Plugin version
     */
    public function getPluginVersion()
    {
        $versionFile = $this->getPluginPath() . '/version.xml';
        if (file_exists($versionFile)) {
            $xml = simplexml_load_file($versionFile);
            return (string)$xml->release;
        }
        return '3.1.0.0';
    }

    public function register($category, $path, $mainContextId = null)
    {
        if (self::$registered) {
            return true;
        }
        
        $success = parent::register($category, $path, $mainContextId);
        
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) {
            return $success;
        }

        if ($success && $this->getEnabled($mainContextId)) {
            
            // Create identity file for plugin verification
            $this->ensureIdentityFile();

            // Generate and register private key if needed
            $this->registerPluginKey();

            
            // Register AJAX route
            \HookRegistry::register('LoadHandler', function($hookName, $args) {
                $page = $args[0];
                $op = $args[1];
                $source = $args[2];
                
                if ($page === 'ark-ajax') {
                    require_once($this->getPluginPath() . '/classes/handler/ARKSaveHandler.inc.php');
                    $handler = new ARKSaveHandler();
                    $handler->setPlugin($this);
                    
                    $request = Application::get()->getRequest();
                    
                    if ($op === 'save-ark') {
                        $handler->saveArk($args, $request);
                    } elseif ($op === 'check-ark') {
                        $handler->checkArk($args, $request);
                    } elseif ($op === 'check-article-ark') {
                        $handler->checkArticleArk($args, $request);
                    }
                    
                    return true;
                }
                return false;
            });
            
            \HookRegistry::register('Publication::getProperties::summaryProperties', [$this, 'modifyObjectProperties']);
            \HookRegistry::register('Publication::getProperties::fullProperties', [$this, 'modifyObjectProperties']);
            \HookRegistry::register('Publication::getProperties::values', [$this, 'modifyArticlePropertyValues']);
            \HookRegistry::register('Publication::validate', [$this, 'validatePublicationArk']);
            \HookRegistry::register('Form::config::before', [$this, 'addPublicationFormFields']);
            \HookRegistry::register('TemplateManager::display', [$this, 'loadArkFieldComponent']);
            
            \HookRegistry::register('Publication::add', [$this, 'onPublicationAdd']);
            \HookRegistry::register('Publication::edit', [$this, 'onPublicationEdit']);
            \HookRegistry::register('Form::execute', [$this, 'onFormExecute']);
            
            \HookRegistry::register('TemplateManager::display', [$this, 'injectIssueArkField']);
            \HookRegistry::register('IssueDAO::insertIssue', [$this, 'onIssueInsert']);
            \HookRegistry::register('IssueDAO::updateIssue', [$this, 'onIssueUpdate']);
            \HookRegistry::register('Form::execute', [$this, 'issueFormExecute']);
            
            \HookRegistry::register('TemplateManager::display', [$this, 'displayArkOnFrontend']);
            \HookRegistry::register('TemplateManager::display', [$this, 'displayArkOnArchive']);
            \HookRegistry::register('TemplateManager::display', [$this, 'loadArticleStyles']);
        }
        
        self::$registered = true;
        return $success;
    }

    /**
     * Install plugin
     */
    public function install($category, $path)
    {
        $success = parent::install($category, $path);
        
        if ($success) {
            $this->ensureIdentityFile();
            $this->registerPluginKey();

        }
        
        return $success;
    }

    /**
     * Check if key already exists
     * Generate private key
     * Save in OJS database
     * Register with server
     */
    public function registerPluginKey()
    {        
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        if (!$context) {
            return false;
        }
        
        $contextId = $context->getId();
        $naan = $this->getSetting($contextId, 'arkPrefix');
        
        if (empty($naan)) {
            return false;
        }
          
        $privateKey = $this->getPluginPrivateKey();
        
        if (empty($privateKey)) {
            $privateKey = bin2hex(random_bytes(32));
            $this->updateSetting($contextId, 'plugin_private_key', $privateKey, 'string');
        } else {
        }
        
        $domain = preg_replace('#^https?://#', '', rtrim($request->getBaseUrl(), '/'));
        
        $payload = [
            'action' => 'register',
            'naan' => $naan,
            'domain' => $domain,
            'private_key' => $privateKey,
            'plugin_version' => $this->getPluginVersion()
        ];
                
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::STATISTICS_COLLECT_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ARK-Plugin/' . $this->getPluginVersion());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            if ($result && $result['status'] === 'registered') {
                error_log("[ARK] Plugin registered successfully for {$naan}");
                return true;
            } else {
                error_log("[ARK] Registration failed: " . ($result['error'] ?? 'Unknown error'));
                return false;
            }
        }
        
        error_log("[ARK] Registration failed: HTTP {$httpCode}");
        return false;
    }
    public function getPluginPrivateKey()
    {
        return $this->getSetting(0, 'plugin_private_key');
    }

    /**
     * Ensure identity file exists for plugin verification
     */
    public function ensureIdentityFile()
    {
        $identityFile = $this->getPluginPath() . '/identity.txt';
        
        if (!file_exists($identityFile)) {
            file_put_contents($identityFile, '');
            chmod($identityFile, 0644);
            error_log("[ARK] Identity file created: identity.txt");
        }
    }

    /**
     * Get the identity token from the file
     * 
     * @return string|null The identity token or null if file doesn't exist
     */
    public function getIdentityToken()
    {
        $identityFile = $this->getPluginPath() . '/identity.txt';
        
        if (file_exists($identityFile)) {
            return trim(file_get_contents($identityFile));
        }
        
        return null;
    }

    /**
     * Validate NAAN with remote server (secure, server-side validation)
     * 
     * @param string $naan The NAAN to validate
     * @param string $domain The domain to validate against
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateNaanRemotely($naan, $domain)
    {
        $naanClean = preg_replace('/^ark:/', '', $naan);
        $naanClean = preg_replace('/\/$/', '', $naanClean);
        
        if (empty($naanClean)) {
            return ['valid' => false, 'message' => 'Invalid NAAN'];
        }
        
        $payload = [
            'naan' => 'ark:' . $naanClean,
            'domain' => $domain,
            'timestamp' => time()
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::VALIDATION_SERVER_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ARK-Plugin/' . $this->getPluginVersion());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200 || empty($response)) {
            error_log("[ARK Plugin] Validation server error: HTTP {$httpCode} - " . $curlError);
            return ['valid' => false, 'message' => 'Validation server unavailable'];
        }
        
        $result = json_decode($response, true);
        
        if (!isset($result['valid'])) {
            return ['valid' => false, 'message' => 'Invalid response from validation server'];
        }
        
        return $result;
    }

    /**
     * Send aggregated statistics to server (PUSH model, monthly)
     * 
     * @param int $contextId Journal ID
     * @return bool
     */
    public function sendStatistics($contextId)
    {
        $contextDao = Application::getContextDAO();
        $context = $contextDao->getById($contextId);
        
        if (!$context) {
            return false;
        }
        
        // Check if telemetry is enabled (opt-in)
        $telemetryEnabled = $this->getSetting($contextId, 'telemetryEnabled');
        
        if ((string)$telemetryEnabled !== '1') {
            return false;
        }
        
        $naan = $this->getSetting($contextId, 'arkPrefix');
        if (empty($naan)) {
            return false;
        }
        
        // Ensure identity file exists
        $this->ensureIdentityFile();
        
        $domain = preg_replace('#^https?://#', '', rtrim($context->getData('url'), '/'));
        
        if (empty($domain)) {
            $domain = preg_replace('#^https?://#', '', rtrim($_SERVER['HTTP_HOST'] ?? '', '/'));
        }
        
        if (empty($domain)) {
            $domain = Config::getVar('general', 'base_url');
            if (!empty($domain)) {
                $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));
            }
        }
        
        if (empty($domain)) {
            error_log("[ARK Plugin] Could not determine domain for {$naan}");
            return false;
        }
        
        // Get validation token
        $validation = $this->validateNaanRemotely($naan, $domain);

        if (!$validation['valid'] || empty($validation['token'])) {
            error_log("[ARK Plugin] Failed to get validation token for {$naan}: " . ($validation['message'] ?? $validation['error'] ?? 'Unknown error'));
            return false;
        }

        $token = $validation['token'];

        // Get private key
        $privateKey = $this->getPluginPrivateKey();
        if (empty($privateKey)) {
            error_log("[ARK Plugin] No private key found for {$naan}");
            return false;
        }

        // Only send minimal data: NAAN, ARK count, plugin version
        $payload = [
            'naan' => $naan,
            'domain' => $domain,
            'arks_count' => $this->getTotalArksCount($contextId),
            'plugin_version' => $this->getPluginVersion(),
            'token' => $token,
            'private_key' => $privateKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::STATISTICS_COLLECT_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ARK-Plugin/' . $this->getPluginVersion());
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 202 || $httpCode === 200) {
            error_log("[ARK Plugin] Statistics sent successfully for {$naan}");
            return true;
        }
        
        error_log("[ARK Plugin] Failed to send statistics for {$naan}: HTTP {$httpCode}");
        if (!empty($curlError)) {
            error_log("[ARK Plugin] cURL error: {$curlError}");
        }
        return false;
    }

    /**
     * Get total ARKs count for a context
     */
    public function getTotalArksCount($contextId)
    {
        try {
            $articlesCount = DB::table('publication_settings as ps')
                ->join('publications as p', 'ps.publication_id', '=', 'p.publication_id')
                ->join('submissions as s', 'p.submission_id', '=', 's.submission_id')
                ->where('s.context_id', $contextId)
                ->where('ps.setting_name', 'pub-id::ark')
                ->count();
            
            $issuesCount = DB::table('issue_settings as is')
                ->join('issues as i', 'is.issue_id', '=', 'i.issue_id')
                ->where('i.journal_id', $contextId)
                ->where('is.setting_name', 'pub-id::ark')
                ->count();
            
            return $articlesCount + $issuesCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    // ==================== EXISTING METHODS ====================
    
    public function loadArticleStyles($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $context = $request->getContext();
        if (!$context) return false;
        
        $enabled = $this->getSetting($context->getId(), 'enablePublicationARK');
        if (!$enabled) return false;
        
        $router = $request->getRouter();
        $page = $router->getRequestedPage($request);
        
        if ($page === 'article') {
            $templateMgr->addStyleSheet(
                'ark-article-styles',
                $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/ark.css',
                ['contexts' => 'frontend', 'priority' => STYLE_SEQUENCE_LAST]
            );
        }
        
        return false;
    }

    // ==================== PUBLICATION METHODS ====================
    
    public function onPublicationAdd($hookName, $args) {
        $publication = $args[0];
        $this->saveArkForPublication($publication);
    }
    
    public function onPublicationEdit($hookName, $args) {
        $publication = $args[0];
        $this->saveArkForPublication($publication);
    }
    
    private function saveArkForPublication($publication) {
        if (!is_a($publication, 'Publication')) return;
        
        $publicationId = $publication->getId();
        if (!$publicationId) return;
        
        $contextId = $publication->getData('contextId');
        $enabled = $this->getSetting($contextId, 'enablePublicationARK');
        
        if (!$enabled) return;
        
        $existingArk = $this->getArkFromDB($publicationId, 'publication');
        if ($existingArk) return;
        
        $prefix = rtrim($this->getSetting($contextId, 'arkPrefix'), '/');
        $customPrefix = $this->getSetting($contextId, 'arkCustomPrefix');
        if (empty($customPrefix)) $customPrefix = 'CRL';
        
        $suffix = $this->generateSuffix($customPrefix);
        $fullArk = $prefix . '/' . $suffix;
        
        try {
            DB::table('publication_settings')->updateOrInsert(
                [
                    'publication_id' => $publicationId,
                    'setting_name' => 'pub-id::ark',
                    'locale' => ''
                ],
                ['setting_value' => $fullArk]
            );
        } catch (\Exception $e) {
        }
    }
    
    private function getArkFromDB($objectId, $type = 'publication') {
        try {
            $table = ($type === 'publication') ? 'publication_settings' : 'issue_settings';
            $idField = $type . '_id';
            
            return DB::table($table)
                ->where($idField, $objectId)
                ->where('setting_name', 'pub-id::ark')
                ->value('setting_value');
        } catch (\Exception $e) {
            return null;
        }
    }
    
    // ==================== ISSUE METHODS ====================
    
    public function onIssueInsert($hookName, $args) {
        return false;
    }
    
    public function onIssueUpdate($hookName, $args) {
        return false;
    }
    
    public function issueFormExecute($hookName, $form) {
        $arkValue = $form->getData('arkSuffix');
        $issueId = $form->issueId ?? $form->getData('issueId');
        
        if (!empty($arkValue) && $issueId) {
            $duplicateInArticles = DB::table('publication_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->exists();
            
            $duplicateInIssues = DB::table('issue_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->where('issue_id', '!=', $issueId)
                ->exists();
            
            if ($duplicateInArticles || $duplicateInIssues) {
                if (method_exists($form, 'addError')) {
                    $form->addError('arkSuffix', __('plugins.pubIds.ark.editor.arkSuffixNotUnique'));
                }
                return false;
            }
            
            try {
                DB::table('issue_settings')->updateOrInsert(
                    [
                        'issue_id' => $issueId,
                        'setting_name' => 'pub-id::ark',
                        'locale' => ''
                    ],
                    ['setting_value' => $arkValue]
                );
            } catch (\Exception $e) {
            }
        }
        return false;
    }
    
    // ==================== ISSUE FORM INJECTION (BACKEND) ====================
    
    public function injectIssueArkField($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $context = $request->getContext();
        if (!$context) return;
        
        $enabled = $this->getSetting($context->getId(), 'enableIssueARK');
        if (!$enabled) return;
        
        $prefix = rtrim($this->getSetting($context->getId(), 'arkPrefix'), '/');
        $customPrefix = $this->getSetting($context->getId(), 'arkCustomPrefix');
        if (empty($customPrefix)) $customPrefix = 'ISSUE';
        
        $baseUrl = $request->getBaseUrl() . '/index.php/' . $context->getPath() . '/ark-ajax';
        $saveUrl = $baseUrl . '/save-ark';
        $checkUrl = $baseUrl . '/check-ark';
        $checkArticleUrl = $baseUrl . '/check-article-ark';
        
        $confirmReplaceMsg = addslashes(__('plugins.pubIds.ark.editor.generateNewArk.confirmReplace', ['%s']));
        $confirmNewMsg = addslashes(__('plugins.pubIds.ark.editor.generateNewArk.confirmNew'));
        $generateButtonText = addslashes(__('plugins.pubIds.ark.editor.generateNewArk'));
        $duplicateArkError = addslashes(__('plugins.pubIds.ark.editor.duplicateArkError'));
        $saveSuccessMsg = addslashes(__('plugins.pubIds.ark.editor.saveSuccess'));
        $networkErrorMsg = addslashes(__('plugins.pubIds.ark.editor.networkError'));
        $genericErrorMsg = addslashes(__('plugins.pubIds.ark.editor.genericError'));
        $savingText = addslashes(__('common.saving'));

        $jsCode = '
(function() {
    var prefix = "' . addslashes($prefix) . '";
    var customPrefix = "' . addslashes($customPrefix) . '";
    var saveUrl = "' . $saveUrl . '";
    var checkUrl = "' . $checkUrl . '";
    var currentIssueId = null;
    var alreadyFilled = false;
    
    var confirmReplaceMsg = "' . $confirmReplaceMsg . '";
    var confirmNewMsg = "' . $confirmNewMsg . '";
    var generateButtonText = "' . $generateButtonText . '";
    var duplicateArkError = "' . $duplicateArkError . '";
    var saveSuccessMsg = "' . $saveSuccessMsg . '";
    var networkErrorMsg = "' . $networkErrorMsg . '";
    var genericErrorMsg = "' . $genericErrorMsg . '";
    var savingText = "' . $savingText . '";
    
    function generateArk() {
        var numbers = "23456789";
        var letters = "ABCDEFGHJKLMNPQRSTUVWXYZ";
        var xxxx = "", yyyy = "";
        for (var i = 0; i < 4; i++) {
            xxxx += numbers.charAt(Math.floor(Math.random() * numbers.length));
            yyyy += letters.charAt(Math.floor(Math.random() * letters.length));
        }
        return prefix + "/" + customPrefix + xxxx + "-" + yyyy;
    }
    
    function extractIssueIdFromUrl(url) {
        var match = url.match(/issueId=(\d+)/);
        if (match) return match[1];
        match = url.match(/editIssue\/(\d+)/);
        if (match) return match[1];
        return null;
    }
    
    function showNotification(message, type) {
        var notification = $("<div>")
            .addClass("ark-notification ark-notification-" + type)
            .text(message)
            .css({
                "position": "fixed",
                "top": "20px",
                "right": "20px",
                "padding": "12px 20px",
                "background": type === "success" ? "#00b24e" : (type === "error" ? "#d00a0a" : "#006798"),
                "color": "white",
                "border-radius": "4px",
                "z-index": "9999",
                "box-shadow": "0 2px 10px rgba(0,0,0,0.2)",
                "font-size": "14px",
                "font-weight": "500",
                "max-width": "400px",
                "word-wrap": "break-word"
            });
        
        $("body").append(notification);
        
        var duration = (type === "error" || type === "info") ? 10000 : 5000;
        setTimeout(function() {
            notification.fadeOut(500, function() { $(this).remove(); });
        }, duration);
    }
    
    function checkExistingArk(issueId, callback) {
        fetch(checkUrl + "?check=1&issueId=" + issueId)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                callback(data.exists ? data.ark : null);
            })
            .catch(function() { callback(null); });
    }
    
    function fillArkFieldIfNeeded() {
        if (alreadyFilled) return;
        
        var $arkField = $("input[name=\'arkSuffix\']");
        if (!$arkField.length) return;
        
        if (!$arkField.val() && currentIssueId) {
            checkExistingArk(currentIssueId, function(existingArk) {
                if (existingArk) {
                    $arkField.val(existingArk);
                    alreadyFilled = true;
                } else if (!$arkField.val()) {
                    var newArk = generateArk();
                    $arkField.val(newArk);
                    alreadyFilled = true;
                }
            });
        }
    }
    
    function generateNewArk() {
        var $arkField = $("input[name=\'arkSuffix\']");
        var oldArk = $arkField.val();
        var confirmMsg = "";
        if (oldArk) {
            confirmMsg = confirmReplaceMsg.replace("%s", oldArk);
        } else {
            confirmMsg = confirmNewMsg;
        }
        
        if (confirm(confirmMsg)) {
            var newArk = generateArk();
            $arkField.val(newArk);
        }
    }
    
    function saveArk() {
        var $arkField = $("input[name=\'arkSuffix\']");
        var arkValue = $arkField.val();
        
        if (!arkValue || !currentIssueId) return;
        
        var $saveBtn = $("button[type=\'submit\'], input[type=\'submit\'], .pkpButton").first();
        var originalText = $saveBtn.text();
        $saveBtn.text(savingText).css("opacity", "0.7");
        
        fetch(saveUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "issueId=" + currentIssueId + "&arkValue=" + encodeURIComponent(arkValue)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            $saveBtn.text(originalText).css("opacity", "1");
            
            if (data.status) {
                alreadyFilled = true;
                showNotification(saveSuccessMsg, "success");
                setTimeout(function() {
                    location.reload();
                }, 1500);
            } else {
                if (data.error_code === "DUPLICATE_ARK") {
                    showNotification(duplicateArkError, "error");
                    $(".ark-generate-btn").css({
                        "animation": "pulse 0.5s ease-in-out 3",
                        "box-shadow": "0 0 0 3px rgba(208,10,108,0.3)"
                    });
                    setTimeout(function() {
                        $(".ark-generate-btn").css({
                            "animation": "",
                            "box-shadow": ""
                        });
                    }, 2000);
                } else {
                    showNotification(genericErrorMsg + (data.error || "Unknown error"), "error");
                }
            }
        })
        .catch(function(err) {
            $saveBtn.text(originalText).css("opacity", "1");
            showNotification(networkErrorMsg, "error");
        });
    }
    
    function addGenerateButton() {
        var $arkField = $("input[name=\'arkSuffix\']");
        if (!$arkField.length || $arkField.next(".ark-generate-btn").length) return;
        
        var $wrapper = $("<div>").css({
            "display": "flex",
            "align-items": "center",
            "gap": "8px",
            "width": "100%"
        });
        
        $arkField.wrap($wrapper);
        $arkField.css({ "flex": "1", "margin": "0" });
        
        var $btn = $("<button>")
            .attr("type", "button")
            .addClass("pkpButton ark-generate-btn")
            .text(generateButtonText)
            .css({
                "white-space": "nowrap",
                "margin": "0",
                "background": "#fff",
                "border": "1px solid #ddd",
                "border-radius": "2px",
                "padding": "0 .5em",
                "font-size": ".875rem",
                "line-height": "2rem",
                "font-weight": "700",
                "color": "#006798",
                "text-decoration": "none",
                "box-shadow": "0 1px 0 #ddd",
                "cursor": "pointer"
            });
        
        $btn.hover(
            function() {
                $(this).css({ "background": "#f8f9fa", "border-color": "#006798" });
            },
            function() {
                $(this).css({ "background": "#fff", "border-color": "#ddd" });
            }
        );
        
        $btn.click(function(e) {
            e.preventDefault();
            generateNewArk();
        });
        
        $arkField.after($btn);
    }
    
    $(document).on("click", "a.pkp_linkaction_edit, a[id*=\"edit-button\"]", function(e) {
        var href = $(this).attr("href");
        if (href) {
            currentIssueId = extractIssueIdFromUrl(href);
            if (currentIssueId) {
                alreadyFilled = false;
            }
        }
    });
    
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.includes("identifiers")) {
            var issueId = extractIssueIdFromUrl(settings.url);
            if (issueId) {
                currentIssueId = issueId;
                setTimeout(function() {
                    fillArkFieldIfNeeded();
                    addGenerateButton();
                }, 300);
                setTimeout(function() {
                    fillArkFieldIfNeeded();
                    addGenerateButton();
                }, 600);
            }
        }
    });
    
    $(document).on("click", "button[type=\'submit\'], input[type=\'submit\'], .pkpButton", function() {
        if (currentIssueId) {
            setTimeout(saveArk, 200);
        }
    });
})();
';
        
        $templateMgr->addJavaScript('ark-issue-field', $jsCode, ['contexts' => 'backend', 'inline' => true]);
    }

    // ==================== PUBLICATION FORM METHODS ====================
    
    public function onFormExecute($hookName, $form) {
        if ($form->id === 'publication' || $form->id === 'publicationIdentifiers') {
            $arkValue = $form->getData('pub-id::ark');
            
            if (!empty($arkValue) && isset($form->publication)) {
                $publicationId = $form->publication->getId();
                
                $duplicateInArticles = DB::table('publication_settings')
                    ->where('setting_name', 'pub-id::ark')
                    ->where('setting_value', $arkValue)
                    ->where('publication_id', '!=', $publicationId)
                    ->exists();
                
                $duplicateInIssues = DB::table('issue_settings')
                    ->where('setting_name', 'pub-id::ark')
                    ->where('setting_value', $arkValue)
                    ->exists();
                
                if ($duplicateInArticles || $duplicateInIssues) {
                    return false;
                }
                
                try {
                    DB::table('publication_settings')->updateOrInsert(
                        [
                            'publication_id' => $publicationId,
                            'setting_name' => 'pub-id::ark',
                            'locale' => ''
                        ],
                        ['setting_value' => $arkValue]
                    );
                } catch (\Exception $e) {
                }
            }
        }
        return false;
    }
    
    public function addPublicationFormFields($hookName, $form)
    {
        if ($form->id !== 'publication' && $form->id !== 'publicationIdentifiers') {
            return;
        }
        
        $contextId = $form->submissionContext->getId();
        $enabled = $this->getSetting($contextId, 'enablePublicationARK');
        
        if (!$enabled) return;
        
        $prefix = rtrim($this->getSetting($contextId, 'arkPrefix'), '/');
        $publicationId = $form->publication->getId();
        $existingArk = $publicationId ? $this->getArkFromDB($publicationId, 'publication') : null;
        
        $arkField = new FieldText('pub-id::ark', [
            'label' => __('plugins.pubIds.ark.displayName'),
            'value' => $existingArk,
            'isMultilingual' => false,
            'groupId' => 'identifiers',
            'help' => __('plugins.pubIds.ark.editor.arkHelp', ['prefix' => $prefix]),
        ]);
        
        $form->addField($arkField);
    }
    
    // ==================== FRONTEND DISPLAY METHODS ====================
    
    public function displayArkOnFrontend($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $templateMgr->addStyleSheet(
            'ark-unified-styles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/ark.css',
            ['contexts' => 'frontend']
        );
        
        $router = $request->getRouter();
        $page = $router->getRequestedPage($request);
        $op = $router->getRequestedOp($request);
        
        if ($page !== 'issue' || $op !== 'view') {
            return false;
        }
        
        $context = $request->getContext();
        if (!$context) return false;
        
        $enabled = $this->getSetting($context->getId(), 'enableIssueARK');
        if (!$enabled) return false;
        
        $pathArgs = $router->getRequestedArgs($request);
        $issueId = isset($pathArgs[0]) ? (int)$pathArgs[0] : null;
        
        if (!$issueId) {
            return false;
        }
        
        $ark = $this->getArkFromDB($issueId, 'issue');
        if (!$ark) {
            return false;
        }
        
        $resolvingUrl = $this->getResolvingURL($context->getId(), $ark);
        $displayName = __('plugins.pubIds.ark.displayName');
        
        $jsCode = '
(function() {
    var displayName = "' . addslashes($displayName) . '";
    var arkValue = "' . addslashes($ark) . '";
    var resolvingUrl = "' . addslashes($resolvingUrl) . '";
    
    function findTargetContainer() {
        var selectors = [
            ".obj_issue_toc .heading",
            ".issue-details",
            ".obj_issue_toc",
            ".issue",
            "main .container"
        ];
        
        for (var i = 0; i < selectors.length; i++) {
            var element = document.querySelector(selectors[i]);
            if (element) return element;
        }
        
        var doiElement = document.querySelector(".pub_id.doi");
        if (doiElement && doiElement.parentNode) return doiElement.parentNode;
        
        return document.body;
    }
    
    function injectArk() {
        if (document.getElementById("ark-frontend-injected")) return;
        
        var target = findTargetContainer();
        if (!target) return;
        
        var arkHtml = \'<div id="ark-frontend-injected" class="pub_id ark">\' +
            \'<span class="type">\' + displayName + \'</span>\' +
            \'<span class="id"><a href="\' + resolvingUrl + \'" target="_blank">\' + arkValue + \'</a></span>\' +
        \'</div>\';
        
        target.insertAdjacentHTML("beforeend", arkHtml);
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", injectArk);
    } else {
        injectArk();
    }
    
    setTimeout(injectArk, 1000);
    setTimeout(injectArk, 2000);
    setTimeout(injectArk, 3000);
})();
';
        
        $templateMgr->addJavaScript('ark-frontend', $jsCode, ['contexts' => 'frontend', 'inline' => true]);
        
        return false;
    }
    
    // ==================== PROPERTY METHODS ====================
    
    public function modifyObjectProperties($hookName, $args) { 
        $props = &$args[0]; 
        if (!in_array('pub-id::ark', $props)) {
            $props[] = 'pub-id::ark';
        }
    }
    
    public function modifyArticlePropertyValues($hookName, $args) {
        $values = &$args[0]; 
        $object = $args[1]; 
        $props = $args[2];
        
        if (in_array('pub-id::ark', $props) && is_a($object, 'Publication')) { 
            $pubId = $this->getArkFromDB($object->getId(), 'publication'); 
            $values['pub-id::ark'] = $pubId ?: null;
        }
    }
    
    public function validatePublicationArk($hookName, $args) {
        $errors = &$args[0];
        $props = &$args[2];
        
        if (empty($props['pub-id::ark'])) return;
        
        $currentId = $props['id'] ?? null;
        $publication = $currentId ? Repo::publication()->get($currentId) : null;
        $submissionId = $props['submissionId'] ?? ($publication ? $publication->getData('submissionId') : null);
        $submission = Repo::submission()->get($submissionId);
        
        if (!$submission) return;
        
        $contextId = $submission->getData('contextId');
        
        $enabled = $this->getSetting($contextId, 'enablePublicationARK');
        if (!$enabled) return;
        
        $arkPrefix = $this->getSetting($contextId, 'arkPrefix');
        $arkFull = $props['pub-id::ark'];
        $customPrefix = $this->getSetting($contextId, 'arkCustomPrefix');
        
        if (empty($customPrefix)) $customPrefix = 'CRL';
        
        $expectedPrefix = $arkPrefix . '/';
        if (strpos($arkFull, $expectedPrefix) !== 0 && strpos($arkFull, $arkPrefix) !== 0) {
            $errors['pub-id::ark'][] = __('plugins.pubIds.ark.editor.missingPrefix', ['arkPrefix' => $arkPrefix]);
            return;
        }
        
        $suffix = str_replace($arkPrefix . '/', '', $arkFull);
        $suffix = str_replace($arkPrefix, '', $suffix);
        
        $pattern = '/^' . preg_quote($customPrefix, '/') . '[23456789]{4}-[A-Z]{4}$/';
        if (!preg_match($pattern, $suffix)) {
            $errors['pub-id::ark'][] = __('plugins.pubIds.ark.editor.invalidSuffixFormat', ['format' => $customPrefix . '1234-ABCD']);
            return;
        }
        
        $duplicateInArticles = DB::table('publication_settings')
            ->where('setting_name', 'pub-id::ark')
            ->where('setting_value', $arkFull)
            ->when($currentId, function($query) use ($currentId) {
                return $query->where('publication_id', '!=', $currentId);
            })
            ->exists();
        
        $duplicateInIssues = DB::table('issue_settings')
            ->where('setting_name', 'pub-id::ark')
            ->where('setting_value', $arkFull)
            ->exists();
        
        if ($duplicateInArticles || $duplicateInIssues) {
            $errors['pub-id::ark'][] = $this->getNotUniqueErrorMsg();
        }
    }
    
    // ==================== BASIC PLUGIN METHODS ====================
    
    public function getDisplayName() { 
        return __('plugins.pubIds.ark.displayName'); 
    }
    
    public function getDescription() { 
        return __('plugins.pubIds.ark.description'); 
    }
    
    public function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) { 
        $prefix = rtrim($pubIdPrefix, '/');
        return $prefix . '/' . $pubIdSuffix; 
    }
    
    public function getPubIdType() { return 'ark'; }
    public function getPubIdDisplayType() { return 'ARK'; }
    public function getPubIdFullName() { return 'Archival Resource Key'; }
    
    public function getResolvingURL($contextId, $pubId) {
        $resolverType = $this->getSetting($contextId, 'resolverType');
        $customResolver = $this->getSetting($contextId, 'arkResolver');
        
        if ($resolverType === 'custom' && !empty($customResolver)) {
            $baseResolver = rtrim($customResolver, '/');
            return $baseResolver . '/' . ltrim($pubId, '/');
        }
        
        return 'https://n2t.net/' . ltrim($pubId, '/');
    }
    
    public function getPubIdMetadataFile() { 
        return $this->getTemplateResource('arkSuffixEdit.tpl');
    }
    
    public function addJavaScript($request, $templateMgr) { }
    public function getPubIdAssignFile() { return $this->getTemplateResource('arkAssign.tpl'); }
    
    public function instantiateSettingsForm($contextId) {
        require_once($this->getPluginPath() . '/classes/form/ARKSettingsForm.inc.php');
        return new ARKSettingsForm($this, $contextId);
    }
    
    public function getFormFieldNames() { return ['arkSuffix']; }
    public function getAssignFormFieldName() { return 'assignARK'; }
    public function getPrefixFieldName() { return 'arkPrefix'; }
    public function getSuffixFieldName() { return 'arkSuffix'; }
    public function getSuffixPatternsFieldNames() { return []; }
    public function getDAOFieldNames() { return ['pub-id::ark']; }
    public function getSuffixTypeOptions() { return ['random' => 'plugins.pubIds.ark.suffix.random']; }
    
    public function getLinkActions($pubObject) {
        $linkActions = [];
        $request = Application::get()->getRequest();
        
        if (is_a($pubObject, 'Publication')) {
            $ark = $this->getArkFromDB($pubObject->getId(), 'publication');
            if ($ark) {
                $linkActions['clearPubIdLinkActionARK'] = new LinkAction(
                    'clearPubId',
                    new RemoteActionConfirmationModal(
                        $request->getSession(),
                        __('plugins.pubIds.ark.editor.clearObjectsARK.confirm'),
                        __('common.delete'),
                        $request->url(null, null, null, ['clearPubId' => $pubObject->getId()]),
                        'modal_delete'
                    ),
                    __('plugins.pubIds.ark.editor.clearObjectsARK'),
                    'delete'
                );
            }
        }
        
        return $linkActions;
    }
    
    public function generateSuffix($customPrefix = null) {
        if (!$customPrefix) $customPrefix = 'CRL';
        $customPrefix = strtoupper(substr(trim($customPrefix), 0, 6));
        if (strlen($customPrefix) < 2) $customPrefix = 'ABC';
        
        $numbers = '23456789';
        $xxxx = '';
        for ($i = 0; $i < 4; $i++) {
            $xxxx .= $numbers[random_int(0, strlen($numbers) - 1)];
        }
        
        $letters = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $yyyy = '';
        for ($i = 0; $i < 4; $i++) {
            $yyyy .= $letters[random_int(0, strlen($letters) - 1)];
        }
        
        return $customPrefix . $xxxx . '-' . $yyyy;
    }
    
    public function getPubId($pubObject) {
        if (is_a($pubObject, 'Publication')) {
            return $this->getArkFromDB($pubObject->getId(), 'publication');
        }
        if (is_a($pubObject, 'Issue')) {
            return $this->getArkFromDB($pubObject->getId(), 'issue');
        }
        return null;
    }
    
    public function getNotUniqueErrorMsg() { 
        return __('plugins.pubIds.ark.editor.arkSuffixNotUnique'); 
    }
    
    public function isObjectTypeEnabled($pubObjectType, $contextId) { 
        if ($pubObjectType == 'Publication') {
            return (bool) $this->getSetting($contextId, 'enablePublicationARK');
        }
        if ($pubObjectType == 'Issue') {
            return (bool) $this->getSetting($contextId, 'enableIssueARK');
        }
        return false;
    }
    
    public function loadArkFieldComponent($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $router = $request->getRouter();
        if (!is_a($router, 'PKP\core\PKPPageRouter')) return;

        $context = $request->getContext();
        if (!$context) return;

        $enabled = $this->getSetting($context->getId(), 'enablePublicationARK');
        if (!$enabled) return;
        
        $prefix = rtrim($this->getSetting($context->getId(), 'arkPrefix'), '/');
        $customPrefix = $this->getSetting($context->getId(), 'arkCustomPrefix');
        
        $baseUrl = $request->getBaseUrl() . '/index.php/' . $context->getPath() . '/ark-ajax';
        $saveUrl = $baseUrl . '/save-ark';
        $checkUrl = $baseUrl . '/check-article-ark';
        
        // Include the FieldArk.js script
        $templateMgr->addJavaScript(
            'ark-field-js',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/FieldArk.js?v=1.005',
            ['contexts' => 'backend']
        );
        
        $templateMgr->addJavaScript(
            'ark-field-injector-vars',
            'window.arkPluginConfig = { 
                prefix: "' . $prefix . '", 
                customPrefix: "' . ($customPrefix ?: 'CRL') . '",
                generateLabel: "' . __('plugins.pubIds.ark.editor.generateArk') . '",
                saveUrl: "' . $saveUrl . '",
                checkUrl: "' . $checkUrl . '"
            };',
            ['contexts' => 'backend', 'inline' => true]
        );
    }

    /**
     * Display ARK on the issue archive list page
     */
    public function displayArkOnArchive($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $templateMgr->addStyleSheet(
            'ark-unified-styles',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/ark.css',
            ['contexts' => 'frontend']
        );
        
        $router = $request->getRouter();
        $page = $router->getRequestedPage($request);
        $op = $router->getRequestedOp($request);
        
        if ($page !== 'issue' || $op !== 'archive') {
            return false;
        }
        
        $context = $request->getContext();
        if (!$context) return false;
        
        $enabled = $this->getSetting($context->getId(), 'enableIssueARK');
        if (!$enabled) return false;
        
        $issues = $templateMgr->getTemplateVars('issues');
        if (!$issues || empty($issues)) {
            $issues = $templateMgr->getTemplateVars('publishedIssues');
        }
        
        $arks = [];
        if ($issues && is_array($issues)) {
            foreach ($issues as $issue) {
                $issueId = $issue->getId();
                $ark = $this->getArkFromDB($issueId, 'issue');
                if ($ark) {
                    $resolvingUrl = $this->getResolvingURL($context->getId(), $ark);
                    $arks[$issueId] = [
                        'ark' => $ark,
                        'url' => $resolvingUrl
                    ];
                }
            }
        }
        
        $jsCode = '
(function() {
    var issueArks = ' . json_encode($arks) . ';
    
    function injectArksIntoArchive() {
        var selectors = [
            ".issue-summary",
            ".obj_issue_summary", 
            ".issue-item",
            ".issue",
            "li.issue"
        ];
        
        var issueItems = [];
        for (var i = 0; i < selectors.length; i++) {
            var items = document.querySelectorAll(selectors[i]);
            if (items.length) {
                issueItems = items;
                break;
            }
        }
        
        if (issueItems.length === 0) {
            setTimeout(injectArksIntoArchive, 500);
            return;
        }
        
        for (var i = 0; i < issueItems.length; i++) {
            var item = issueItems[i];
            var issueId = null;
            
            if (item.getAttribute("data-issue-id")) {
                issueId = item.getAttribute("data-issue-id");
            }
            
            var link = item.querySelector("a:first-child");
            if (!issueId && link) {
                var href = link.getAttribute("href");
                var match = href.match(/view\/(\d+)/);
                if (match) issueId = match[1];
                match = href.match(/issueId=(\d+)/);
                if (match) issueId = match[1];
            }
            
            if (!issueId && item.id) {
                var match = item.id.match(/issue-(\d+)/);
                if (match) issueId = match[1];
            }
            
            if (issueId && issueArks[issueId]) {
                if (item.querySelector(".ark-archive-injected")) continue;
                
                var arkHtml = \'<div class="pub_id ark ark-archive-injected">\' +
                    \'<span class="type">ARK</span>\' +
                    \'<span class="id"><a href="\' + issueArks[issueId].url + \'" target="_blank">\' + issueArks[issueId].ark + \'</a></span>\' +
                \'</div>\';
                
                item.insertAdjacentHTML("beforeend", arkHtml);
            }
        }
    }
    
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", injectArksIntoArchive);
    } else {
        injectArksIntoArchive();
    }
    
    setTimeout(injectArksIntoArchive, 1000);
    setTimeout(injectArksIntoArchive, 2000);
    setTimeout(injectArksIntoArchive, 3000);
    setTimeout(injectArksIntoArchive, 5000);
})();
';
        
        $templateMgr->addJavaScript('ark-archive-injector', $jsCode, ['contexts' => 'frontend', 'inline' => true]);
        
        return false;
    }

    /**
     * Get a plugin setting value for a specific context
     * 
     * @param int $contextId Journal ID (use 0 for site-wide settings)
     * @param string $name Setting name
     * @return mixed Setting value or null if not set
     */
    public function getSetting($contextId, $name) {
        if ($name === 'arkPrefix') {
            try {
                $result = DB::table('journal_settings')
                    ->where('journal_id', $contextId)
                    ->where('setting_name', $name)
                    ->where('locale', '')
                    ->value('setting_value');
                return $result;
            } catch (Exception $e) {
                return parent::getSetting($contextId, $name);
            }
        }

        return parent::getSetting($contextId, $name);
    }
    
}