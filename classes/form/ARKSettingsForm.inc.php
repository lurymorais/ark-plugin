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
        
        $this->setData('enablePublicationARK', $plugin->getSetting($contextId, 'enablePublicationARK'));
        $this->setData('enableIssueARK', $plugin->getSetting($contextId, 'enableIssueARK'));
        $this->setData('arkPrefix', $plugin->getSetting($contextId, 'arkPrefix'));
        $this->setData('arkSuffix', $plugin->getSetting($contextId, 'arkSuffix'));
        $this->setData('arkCustomPrefix', $plugin->getSetting($contextId, 'arkCustomPrefix'));
        
        // Get implementation date directly from database - bypass plugin cache
        $result = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkImplementationDate')
            ->where('locale', '')
            ->first();
        
        $implDate = $result ? $result->setting_value : '';
        $this->setData('arkImplementationDate', $implDate);
        
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
            'arkResolver'
        ]);
    }

    public function execute(...$functionArgs) {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        $enablePublication = $this->getData('enablePublicationARK');
        $enableIssue = $this->getData('enableIssueARK');
        $arkPrefix = $this->getData('arkPrefix');
        $arkSuffix = $this->getData('arkSuffix');
        $arkCustomPrefix = $this->getData('arkCustomPrefix');
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        $arkImplementationDate = $this->getData('arkImplementationDate');
        
        try {
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'enablePublicationARK', 'locale' => ''],
                ['setting_value' => $enablePublication ? '1' : '0']
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'enableIssueARK', 'locale' => ''],
                ['setting_value' => $enableIssue ? '1' : '0']
            );
            
            DB::table('journal_settings')->updateOrInsert(
                ['journal_id' => $contextId, 'setting_name' => 'arkPrefix', 'locale' => ''],
                ['setting_value' => $arkPrefix]
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
            
        } catch (\Exception $e) {
            error_log("[ARK Settings] Error saving settings: " . $e->getMessage());
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}