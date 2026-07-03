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
        
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        $templateMgr->assign('arkPrefix', $plugin->getSetting($contextId, 'arkPrefix'));
        $templateMgr->assign('arkCount', $plugin->getTotalArksCount($contextId));
        $templateMgr->assign('pluginVersion', $plugin->getPluginVersion());
        
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
        
        $arkPrefix = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkPrefix')
            ->where('locale', '')
            ->value('setting_value');
        
        $arkImplementationDate = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkImplementationDate')
            ->where('locale', '')
            ->value('setting_value');
        
        $telemetryEnabled = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'telemetryEnabled')
            ->where('locale', '')
            ->value('setting_value');
        
        if ($telemetryEnabled === null) {
            $telemetryEnabled = '1';
        }
        
        $this->setData('enablePublicationARK', $plugin->getSetting($contextId, 'enablePublicationARK'));
        $this->setData('enableIssueARK', $plugin->getSetting($contextId, 'enableIssueARK'));
        $this->setData('arkPrefix', $arkPrefix);
        $this->setData('arkSuffix', $plugin->getSetting($contextId, 'arkSuffix'));
        $this->setData('arkCustomPrefix', $plugin->getSetting($contextId, 'arkCustomPrefix'));
        $this->setData('arkImplementationDate', $arkImplementationDate);
        $this->setData('telemetryEnabled', $telemetryEnabled);
            
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
        $validation = $plugin->validateNaanRemotely($naan, $domain);
        
        if (!$validation['valid']) {
            $this->addError('arkPrefix', $validation['message']);
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
        
        // If checkbox was not sent in POST (unchecked)
        if ($telemetryEnabled === null) {
            // Check if a value already exists in the database
            $existingValue = DB::table('journal_settings')
                ->where('journal_id', $contextId)
                ->where('setting_name', 'telemetryEnabled')
                ->where('locale', '')
                ->value('setting_value');
            
            // If never saved before (first time), default to enabled
            // If already saved, user unchecked it
            if ($existingValue === null) {
                $telemetryEnabled = '1'; // First time: enabled by default
            } else {
                $telemetryEnabled = '0'; // User unchecked it
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
                ['journal_id' => $contextId, 'setting_name' => 'telemetryEnabled', 'locale' => ''],
                ['setting_value' => $telemetryEnabled ? '1' : '0']
            );
            
            if ($resolverType === 'custom') {
                DB::table('journal_settings')->updateOrInsert(
                    ['journal_id' => $contextId, 'setting_name' => 'arkResolver', 'locale' => ''],
                    ['setting_value' => $arkResolver]
                );
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