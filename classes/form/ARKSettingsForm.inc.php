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
        
        // Only for articles (publications)
        $this->addCheck(new FormValidatorCustom($this, 'enablePublicationARK', 'required', 
            'plugins.pubIds.ark.manager.settings.arkObjectsRequired', 
            function($enablePublicationARK) use ($form) {
                return $form->getData('enablePublicationARK') == true;
            }
        ));

        // ARK prefix validation
        $this->addCheck(new FormValidatorRegExp($this, 'arkPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkPrefixPattern', 
            '/^[A-Za-z0-9_:]{2,40}$/'
        ));

        // Custom prefix validation
        $this->addCheck(new FormValidatorRegExp($this, 'arkCustomPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkCustomPrefixPattern', 
            '/^[A-Z]{2,6}$/'
        ));

        // Conditional custom resolver validation
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
        
        // ARK implementation date validation
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
        $this->setData('arkPrefix', $plugin->getSetting($contextId, 'arkPrefix'));
        $this->setData('arkSuffix', $plugin->getSetting($contextId, 'arkSuffix'));
        $this->setData('arkCustomPrefix', $plugin->getSetting($contextId, 'arkCustomPrefix'));
        $this->setData('arkImplementationDate', $plugin->getSetting($contextId, 'arkImplementationDate'));
        
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
        
        $incomingDate = $this->getData('arkImplementationDate');
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        
        // Save ARK implementation date directly to database
        $db = DB::connection();
        $db->table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkImplementationDate')
            ->delete();
        
        if (!empty($incomingDate)) {
            $db->table('journal_settings')->insert([
                'journal_id' => $contextId,
                'setting_name' => 'arkImplementationDate',
                'setting_value' => $incomingDate,
                'locale' => ''
            ]);
        }
        
        // Save other settings using plugin method
        $plugin->updateSetting($contextId, 'enablePublicationARK', $this->getData('enablePublicationARK'), 'bool');
        $plugin->updateSetting($contextId, 'arkPrefix', $this->getData('arkPrefix'), 'string');
        $plugin->updateSetting($contextId, 'arkSuffix', $this->getData('arkSuffix'), 'string');
        $plugin->updateSetting($contextId, 'arkCustomPrefix', $this->getData('arkCustomPrefix'), 'string');
        $plugin->updateSetting($contextId, 'resolverType', $resolverType, 'string');
        
        if ($resolverType === 'custom') {
            $plugin->updateSetting($contextId, 'arkResolver', $arkResolver, 'string');
        } else {
            $plugin->updateSetting($contextId, 'arkResolver', '', 'string');
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}