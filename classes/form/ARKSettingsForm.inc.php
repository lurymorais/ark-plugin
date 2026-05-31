<?php

/**
 * @file plugins/pubIds/ark/classes/form/ARKSettingsForm.inc.php
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ARKSettingsForm
 * @ingroup plugins_pubIds_ark
 *
 * @brief Form for journal managers to setup ARK plugin
 */

use PKP\form\Form;
use PKP\form\validation\FormValidatorCustom;
use PKP\form\validation\FormValidatorRegExp;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;
use Illuminate\Support\Facades\DB;

class ARKSettingsForm extends Form {

    private $_contextId;
    private $_plugin;

    public function fetch($request, $template = null, $display = false)
    {
        $templateMgr = TemplateManager::getManager($request);
        $baseUrl = $request->getBaseUrl();
        $correctTarget = $baseUrl . '/plugins/pubIds/ark/resolver.php?ark=${value}';
        $templateMgr->assign('arkTargetHint', $correctTarget);
        
        return parent::fetch($request, $template, $display);
    }

    public function _getContextId() { return $this->_contextId; }
    public function _getPlugin() { return $this->_plugin; }

    function __construct($plugin, $contextId) {
        $this->_contextId = $contextId;
        $this->_plugin = $plugin;

        parent::__construct($plugin->getTemplateResource('settingsForm.tpl'));

        $form = $this;
        
        $this->addCheck(new FormValidatorCustom($this, 'enablePublicationARK', 'required', 
            'plugins.pubIds.ark.manager.settings.arkObjectsRequired', 
            function($enablePublicationARK) use ($form) {
                return $form->getData('enablePublicationARK') == true;
            }
        ));

        $this->addCheck(new FormValidatorRegExp($this, 'arkPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkPrefixPattern', 
            '/^[A-Za-z0-9_:]{2,40}$/'
        ));

        $this->addCheck(new FormValidatorRegExp($this, 'arkCustomPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkCustomPrefixPattern', 
            '/^[A-Z]{2,6}$/'
        ));

        $this->addCheck(new FormValidatorCustom($this, 'arkResolver', 'optional', 
            'plugins.pubIds.ark.manager.settings.form.arkResolverRequired',
            function($arkResolver) use ($form) {
                $resolverType = $form->getData('resolverType');
                if ($resolverType === 'custom') {
                    if (empty($arkResolver)) {
                        return false;
                    }
                    return filter_var($arkResolver, FILTER_VALIDATE_URL) !== false;
                }
                return true;
            }
        ));
        
        $this->addCheck(new FormValidatorRegExp($this, 'arkImplementationDate', 'optional', 
            'plugins.pubIds.ark.manager.settings.form.arkImplementationDatePattern', 
            '/^(19|20)\d{6}$/'
        ));
        
        $this->addCheck(new FormValidatorPost($this));
        $this->addCheck(new FormValidatorCSRF($this));

        $this->setData('pluginName', $plugin->getName());
    }

    public function initData() {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        // Read arkPrefix directly from database (bypass cache)
        $arkPrefix = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkPrefix')
            ->where('locale', '')
            ->value('setting_value');
        
        // Read telemetryLevel directly from database (bypass cache)
        $telemetryLevel = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'telemetryLevel')
            ->where('locale', '')
            ->value('setting_value');
        
        // Fallback to default if not found
        if (empty($telemetryLevel)) {
            $telemetryLevel = $plugin->getSetting($contextId, 'telemetryLevel');
            if (empty($telemetryLevel)) {
                $telemetryLevel = 'restricted';
            }
        }
        
        $arkImplementationDate = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkImplementationDate')
            ->where('locale', '')
            ->value('setting_value');
        
        $this->setData('enablePublicationARK', $plugin->getSetting($contextId, 'enablePublicationARK'));
        $this->setData('enableIssueARK', $plugin->getSetting($contextId, 'enableIssueARK'));
        $this->setData('arkPrefix', $arkPrefix);
        $this->setData('arkSuffix', $plugin->getSetting($contextId, 'arkSuffix'));
        $this->setData('arkCustomPrefix', $plugin->getSetting($contextId, 'arkCustomPrefix'));
        $this->setData('arkImplementationDate', $arkImplementationDate);
        $this->setData('telemetryLevel', $telemetryLevel);
            
        $resolverType = $plugin->getSetting($contextId, 'resolverType');
        if (empty($resolverType)) {
            $resolverType = 'n2t';
        }
        $this->setData('resolverType', $resolverType);
        
        if ($resolverType === 'custom') {
            $this->setData('arkResolver', $plugin->getSetting($contextId, 'arkResolver'));
        } else {
            $this->setData('arkResolver', '');
        }
        
        $this->setData('lastRecoveryAttempt', $plugin->getSetting($contextId, 'lastRecoveryAttempt'));
    }

    public function readInputData() {
        $this->readUserVars([
            'enablePublicationARK',
            'enableIssueARK',
            'arkPrefix',
            'arkSuffix',
            'arkCustomPrefix',
            'arkImplementationDate',
            'resolverType',
            'arkResolver',
            'telemetryLevel'
        ]);
    }

    /**
     * Handle token recovery via AJAX
     */
    public function handleTokenRecovery($request)
    {
        $plugin = $this->_getPlugin();
        $contextId = $this->_getContextId();
        
        // Check rate limiting (once per hour)
        $lastAttempt = $plugin->getSetting($contextId, 'lastRecoveryAttempt');
        if ($lastAttempt && (time() - $lastAttempt) < 3600) {
            $timeRemaining = 3600 - (time() - $lastAttempt);
            $minutes = ceil($timeRemaining / 60);
            return [
                'success' => false,
                'message' => __('plugins.pubIds.ark.recovery.rateLimit', ['minutes' => $minutes])
            ];
        }
        
        // Update last attempt timestamp
        $plugin->updateSetting($contextId, 'lastRecoveryAttempt', time());
        
        // Request token recovery
        $result = $plugin->requestTokenRecovery($contextId);
        
        return $result;
    }

    /**
     * Execute the form - Save settings
     */
    public function execute(...$functionArgs) {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        $naan = $this->getData('arkPrefix');
        
        // Get context correctly
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        if (!$context || $context->getId() != $contextId) {
            $contextDao = Application::getContextDAO();
            $context = $contextDao->getById($contextId);
        }
        
        $telemetryData = [];
        $telemetryLevel = $this->getData('telemetryLevel');
        
        if ($telemetryLevel === 'public' && $context) {
            $telemetryData = [
                'journal_name' => $context->getData('name'),
                'country' => $context->getData('country'),
                'email' => $context->getData('contactEmail'),
                'primary_language' => $context->getPrimaryLocale()
            ];
        }
        
        // Validation temporarily disabled for testing
        // $validation = $plugin->validateNaanWithTelemetry($naan, $contextId, $telemetryData);
        //
        // if (!$validation['valid']) {
        //     $this->addError('arkPrefix', $validation['message']);
        //     return false;
        // }
        
        // Save settings
        $enablePublication = $this->getData('enablePublicationARK');
        $enableIssue = $this->getData('enableIssueARK');
        $arkSuffix = $this->getData('arkSuffix');
        $arkCustomPrefix = $this->getData('arkCustomPrefix');
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        $arkImplementationDate = $this->getData('arkImplementationDate');
        $telemetryLevel = $this->getData('telemetryLevel');

        // After saving settings, register with central server
        $plugin = $this->_getPlugin();
        $contextId = $this->_getContextId();
        $naan = $this->getData('arkPrefix');
        $token = $plugin->getPluginToken($contextId);
        $adminSecret = $plugin->getSetting($contextId, 'ark_admin_secret');
        if (empty($adminSecret)) {
            $adminSecret = bin2hex(random_bytes(32));
            $plugin->updateSetting($contextId, 'ark_admin_secret', $adminSecret);
        }
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $apiEndpoint = $request->getBaseUrl() . '/index.php/' . $context->getPath() . '/ark-api/telemetry';
        $payload = [
            'naan' => $naan,
            'plugin_ark_token' => $token,
            'admin_secret' => $adminSecret,
            'api_endpoint' => $apiEndpoint,
            'journal_url' => $request->getBaseUrl(),
            'plugin_version' => $plugin->getPluginVersion(),
            'telemetry_level' => $this->getData('telemetryLevel'),
            'arks_count' => $plugin->getTotalArksCount($contextId)
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://revistacarnaubais.com.br/ark-telemetry/register.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $res = json_decode($response, true);
            if ($res['success']) {
                $plugin->updateSetting($contextId, 'next_pull_at', $res['next_pull_at']);
            }
        }
        
        try {
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'arkPrefix', 'locale' => ''],
                ['setting_value' => $naan]
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'enablePublicationARK', 'locale' => ''],
                ['setting_value' => $enablePublication ? '1' : '0']
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'enableIssueARK', 'locale' => ''],
                ['setting_value' => $enableIssue ? '1' : '0']
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'arkSuffix', 'locale' => ''],
                ['setting_value' => $arkSuffix]
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'arkCustomPrefix', 'locale' => ''],
                ['setting_value' => $arkCustomPrefix]
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'resolverType', 'locale' => ''],
                ['setting_value' => $resolverType]
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'arkImplementationDate', 'locale' => ''],
                ['setting_value' => $arkImplementationDate]
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'telemetryLevel', 'locale' => ''],
                ['setting_value' => $telemetryLevel]
            );
            
            if ($resolverType === 'custom') {
                DB::table('journal_settings')->updateOrInsert(
                    ['journal_id' => $contextId, 'setting_name' => 'arkResolver', 'locale' => ''],
                    ['setting_value' => $arkResolver]
                );
            } else {
                DB::table('journal_settings')->updateOrInsert(
                    ['journal_id' => $contextId, 'setting_name' => 'arkResolver', 'locale' => ''],
                    ['setting_value' => '']
                );
            }
            
            // Generate token if it doesn't exist yet
            $plugin->initializePluginToken($contextId);
            
            // Send telemetry to API
            $plugin->sendTelemetryData($contextId, $telemetryLevel);
            
        } catch (\Exception $e) {
            $this->addError('form', 'Error saving settings: ' . $e->getMessage());
            return false;
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}