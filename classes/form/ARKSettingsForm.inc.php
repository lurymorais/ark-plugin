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
use PKP\form\validation\FormValidatorUrl;
use PKP\form\validation\FormValidatorPost;
use PKP\form\validation\FormValidatorCSRF;

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
        
        // Apenas artigos (publications)
        $this->addCheck(new FormValidatorCustom($this, 'enablePublicationARK', 'required', 
            'plugins.pubIds.ark.manager.settings.arkObjectsRequired', 
            function($enablePublicationARK) use ($form) {
                return $form->getData('enablePublicationARK') == true;
            }
        ));

        // Validação do prefixo ARK
        $this->addCheck(new FormValidatorRegExp($this, 'arkPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkPrefixPattern', 
            '/^[A-Za-z0-9_:]{2,40}$/'
        ));

        // Validação do prefixo personalizado
        $this->addCheck(new FormValidatorRegExp($this, 'arkCustomPrefix', 'required', 
            'plugins.pubIds.ark.manager.settings.form.arkCustomPrefixPattern', 
            '/^[A-Z]{2,6}$/'
        ));

        // Validação condicional do resolvedor personalizado
        $this->addCheck(new FormValidatorCustom($this, 'arkResolver', 'optional', 
            'plugins.pubIds.ark.manager.settings.form.arkResolverRequired',
            function($arkResolver) use ($form) {
                $resolverType = $form->getData('resolverType');
                // Se for custom, precisa ter valor e ser URL válida
                if ($resolverType === 'custom') {
                    if (empty($arkResolver)) {
                        return false;
                    }
                    // Valida URL
                    return filter_var($arkResolver, FILTER_VALIDATE_URL) !== false;
                }
                return true;
            }
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
        
        $resolverType = $plugin->getSetting($contextId, 'resolverType');
        if (empty($resolverType)) {
            $resolverType = 'n2t';
        }
        $this->setData('resolverType', $resolverType);
        
        // Só carrega o arkResolver se o tipo for custom
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
            'resolverType',
            'arkResolver'
        ]);
    }

    public function execute(...$functionArgs) {
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        
        // Salva configurações básicas
        $plugin->updateSetting($contextId, 'enablePublicationARK', $this->getData('enablePublicationARK'), 'bool');
        $plugin->updateSetting($contextId, 'arkPrefix', $this->getData('arkPrefix'), 'string');
        $plugin->updateSetting($contextId, 'arkSuffix', $this->getData('arkSuffix'), 'string');
        $plugin->updateSetting($contextId, 'arkCustomPrefix', $this->getData('arkCustomPrefix'), 'string');
        $plugin->updateSetting($contextId, 'resolverType', $resolverType, 'string');
        
        // Só salva o arkResolver se o tipo for custom
        if ($resolverType === 'custom') {
            $plugin->updateSetting($contextId, 'arkResolver', $arkResolver, 'string');
        } else {
            $plugin->updateSetting($contextId, 'arkResolver', '', 'string');
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}
