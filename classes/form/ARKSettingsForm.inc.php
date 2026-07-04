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
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        $templateMgr = TemplateManager::getManager($request);
        $baseUrl = $request->getBaseUrl();
        $correctTarget = $baseUrl . '/plugins/pubIds/ark/resolver.php?ark=${value}';
        $templateMgr->assign('arkTargetHint', $correctTarget);
        
        // Get all settings
        $arkPrefix = $plugin->getSetting($contextId, 'arkPrefix');
        $enablePublicationARK = $plugin->getSetting($contextId, 'enablePublicationARK');
        $enableIssueARK = $plugin->getSetting($contextId, 'enableIssueARK');
        $arkSuffix = $plugin->getSetting($contextId, 'arkSuffix');
        $arkCustomPrefix = $plugin->getSetting($contextId, 'arkCustomPrefix');
        $arkImplementationDate = $plugin->getSetting($contextId, 'arkImplementationDate');
        $telemetryEnabled = $plugin->getSetting($contextId, 'telemetryEnabled');
        $resolverType = $plugin->getSetting($contextId, 'resolverType');
        $arkResolver = $plugin->getSetting($contextId, 'arkResolver');
        
        // Assign to template
        $templateMgr->assign('arkPrefix', $arkPrefix);
        $templateMgr->assign('enablePublicationARK', $enablePublicationARK);
        $templateMgr->assign('enableIssueARK', $enableIssueARK);
        $templateMgr->assign('arkSuffix', $arkSuffix);
        $templateMgr->assign('arkCustomPrefix', $arkCustomPrefix);
        $templateMgr->assign('arkImplementationDate', $arkImplementationDate);
        $templateMgr->assign('telemetryEnabled', $telemetryEnabled);
        $templateMgr->assign('resolverType', $resolverType ?? 'n2t');
        $templateMgr->assign('arkResolver', $arkResolver ?? '');
        
        // Get count and version
        $arkCount = $plugin->getTotalArksCount($contextId);
        $pluginVersion = $plugin->getPluginVersion();
        $templateMgr->assign('arkCount', $arkCount);
        $templateMgr->assign('pluginVersion', $pluginVersion);
        
        return parent::fetch($request, $template, $display);
    }

    public function _getContextId() { 
        return $this->_contextId; 
    }
    
    public function _getPlugin() { 
        return $this->_plugin; 
    }

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
        
        // Default values
        $defaultEnablePublication = '1';
        $defaultEnableIssue = '1';
        $defaultResolverType = 'n2t';
        $defaultTelemetryEnabled = '1';
        
        // Read from database, use defaults if not exists
        $enablePublicationARK = $plugin->getSetting($contextId, 'enablePublicationARK');
        if ($enablePublicationARK === null) {
            $enablePublicationARK = $defaultEnablePublication;
        }
        
        $enableIssueARK = $plugin->getSetting($contextId, 'enableIssueARK');
        if ($enableIssueARK === null) {
            $enableIssueARK = $defaultEnableIssue;
        }
        
        $telemetryEnabled = $plugin->getSetting($contextId, 'telemetryEnabled');
        if ($telemetryEnabled === null) {
            $telemetryEnabled = $defaultTelemetryEnabled;
        }
        
        $resolverType = $plugin->getSetting($contextId, 'resolverType');
        if (empty($resolverType)) {
            $resolverType = $defaultResolverType;
        }
        
        // Set form data
        $this->setData('enablePublicationARK', $enablePublicationARK);
        $this->setData('enableIssueARK', $enableIssueARK);
        $this->setData('arkPrefix', $plugin->getSetting($contextId, 'arkPrefix'));
        $this->setData('arkSuffix', $plugin->getSetting($contextId, 'arkSuffix'));
        $this->setData('arkCustomPrefix', $plugin->getSetting($contextId, 'arkCustomPrefix'));
        $this->setData('arkImplementationDate', $plugin->getSetting($contextId, 'arkImplementationDate'));
        $this->setData('telemetryEnabled', $telemetryEnabled);
        $this->setData('resolverType', $resolverType);
        
        if ($resolverType === 'custom') {
            $this->setData('arkResolver', $plugin->getSetting($contextId, 'arkResolver'));
        } else {
            $this->setData('arkResolver', '');
        }
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
            'telemetryEnabled'
        ]);
    }

    public function execute(...$functionArgs) {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        $naan = $this->getData('arkPrefix');
        
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        if (!$context || $context->getId() != $contextId) {
            $contextDao = Application::getContextDAO();
            $context = $contextDao->getById($contextId);
        }
        
        // ========== NAAN VALIDATION ==========
        $domain = preg_replace('#^https?://#', '', rtrim($request->getBaseUrl(), '/'));
        
        try {
            $validation = $plugin->validateNaanRemotely($naan, $domain);
            
            if (!$validation['valid']) {
                $errorMessage = $validation['message'] ?? $validation['error'] ?? $validation['details'] ?? 'NAAN validation failed';
                $this->addError('arkPrefix', $errorMessage);
                return false;
            }
        } catch (Exception $e) {
            $this->addError('arkPrefix', 'Validation error: ' . $e->getMessage());
            return false;
        }

        // ========== SAVE SETTINGS ==========
        $enablePublication = $this->getData('enablePublicationARK');
        $enableIssue = $this->getData('enableIssueARK');
        $arkSuffix = $this->getData('arkSuffix');
        $arkCustomPrefix = $this->getData('arkCustomPrefix');
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        $arkImplementationDate = $this->getData('arkImplementationDate');

        // ========== TELEMETRY: OPT-OUT ==========
        $telemetryEnabled = $this->getData('telemetryEnabled');
        
        if ($telemetryEnabled === null) {
            $existingValue = DB::table('journal_settings')
                ->where('journal_id', $contextId)
                ->where('setting_name', 'telemetryEnabled')
                ->where('locale', '')
                ->value('setting_value');
            
            if ($existingValue === null) {
                $telemetryEnabled = '1';
            } else {
                $telemetryEnabled = '0';
            }
        }

        // ========== RECORD CONSENT CHANGE (LGPD/GDPR Compliance) ==========
        $newTelemetryEnabled = $this->getData('telemetryEnabled') ? '1' : '0';
        $oldTelemetryEnabled = $plugin->getSetting($contextId, 'telemetryEnabled');

        if ($newTelemetryEnabled !== $oldTelemetryEnabled) {
            $action = $newTelemetryEnabled === '1' ? 'enabled' : 'disabled';
            
            try {
                $request = Application::get()->getRequest();
                $domain = preg_replace('#^https?://#', '', rtrim($request->getBaseUrl(), '/'));
                $message = "Consent changed from '{$oldTelemetryEnabled}' to '{$newTelemetryEnabled}'";
                
                $stmt = DB::connection()->getPdo()->prepare("
                    INSERT INTO ark_validations (
                        naan, 
                        domain, 
                        status, 
                        message,
                        consent_action,
                        consent_previous_value,
                        consent_changed_at
                    ) VALUES (?, ?, 'consent_change', ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $naan,
                    $domain,
                    $message,
                    $action,
                    $oldTelemetryEnabled ?: 'not_set'
                ]);
                
                error_log("[ARK] Consent {$action} for NAAN: {$naan}");
                
            } catch (Exception $e) {
                // Don't interrupt flow, just log
                error_log("[ARK] Failed to log consent change: " . $e->getMessage());
            }
        }

        // ========== SEND STATISTICS ==========
        if ($telemetryEnabled === '1') {
            $plugin->sendStatistics($contextId);
        } else {
            error_log("[ARK] Telemetry disabled for NAAN: {$naan}");
        }
        
        try {
            // Save using official plugin method (plugin_settings)
            $plugin->updateSetting($contextId, 'arkPrefix', $naan, 'string');
            $plugin->updateSetting($contextId, 'enablePublicationARK', $enablePublication ? '1' : '0', 'bool');
            $plugin->updateSetting($contextId, 'enableIssueARK', $enableIssue ? '1' : '0', 'bool');
            $plugin->updateSetting($contextId, 'arkSuffix', $arkSuffix, 'string');
            $plugin->updateSetting($contextId, 'arkCustomPrefix', $arkCustomPrefix, 'string');
            $plugin->updateSetting($contextId, 'resolverType', $resolverType, 'string');
            $plugin->updateSetting($contextId, 'arkImplementationDate', $arkImplementationDate, 'string');
            $plugin->updateSetting($contextId, 'telemetryEnabled', $telemetryEnabled ? '1' : '0', 'bool');
            
            if ($resolverType === 'custom') {
                $plugin->updateSetting($contextId, 'arkResolver', $arkResolver, 'string');
            }
            
            // ========== SEND STATISTICS ==========
            if ($telemetryEnabled === '1') {
                $plugin->sendStatistics($contextId);
            }
            
        } catch (Exception $e) {
            $this->addError('form', 'Error saving settings: ' . $e->getMessage());
            return false;
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}