<?php

/**
 * @file plugins/pubIds/ark/ARKPubIdPlugin.inc.php
 *
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * @class ARKPubIdPlugin
 * @ingroup plugins_pubIds_ark
 *
 * @brief ARK PubId plugin for OJS 3.5.x - Apenas para artigos
 */

use PKP\plugins\PKPPubIdPlugin;
use PKP\config\Config;
use PKP\linkAction\LinkAction;
use PKP\linkAction\request\RemoteActionConfirmationModal;
use PKP\template\TemplateManager;
use APP\facades\Repo;
use APP\core\Application;
use Illuminate\Support\Facades\DB;
use PKP\components\forms\FormComponent;
use PKP\components\forms\FieldText;

class ARKPubIdPlugin extends PKPPubIdPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if (!Config::getVar('general', 'installed') || defined('RUNNING_UPGRADE')) return $success;

        if ($success && $this->getEnabled($mainContextId)) {
            \HookRegistry::register('Publication::getProperties::summaryProperties', [$this, 'modifyObjectProperties']);
            \HookRegistry::register('Publication::getProperties::fullProperties', [$this, 'modifyObjectProperties']);
            \HookRegistry::register('Publication::getProperties::values', [$this, 'modifyObjectPropertyValues']);
            \HookRegistry::register('Publication::validate', [$this, 'validatePublicationArk']);
            \HookRegistry::register('TemplateManager::display', [$this, 'loadArkFieldComponent']);
            
            // Hook para adicionar o campo no formulário de publicação
            \HookRegistry::register('Form::config::before', [$this, 'addPublicationFormFields']);
        }
        return $success;
    }

    public function getDisplayName() { return __('plugins.pubIds.ark.displayName'); }
    public function getDescription() { return __('plugins.pubIds.ark.description'); }
    public function constructPubId($pubIdPrefix, $pubIdSuffix, $contextId) { 
        return $pubIdPrefix . '/' . $pubIdSuffix; 
    }
    public function getPubIdType() { return 'ark'; }
    public function getPubIdDisplayType() { return 'ARK'; }
    public function getPubIdFullName() { return 'Archival Resource Key'; }
    public function getResolvingURL($contextId, $pubId) {
        $resolverType = $this->getSetting($contextId, 'resolverType');
        
        // Se for n2t ou não definido, usa o resolvedor global
        if ($resolverType !== 'custom') {
            return 'https://n2t.net/' . $pubId;
        }
        
        // Se for custom, usa o resolver personalizado
        $resolver = $this->getSetting($contextId, 'arkResolver');
        if (!empty($resolver)) {
            return rtrim($resolver, '/') . '/' . $pubId;
        }
        
        // Fallback para n2t
        return 'https://n2t.net/' . $pubId;
    }
    public function getPubIdMetadataFile() { return $this->getTemplateResource('arkSuffixEdit.tpl'); }
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

    public function getLinkActions($pubObject) {
        $linkActions = [];
        $request = Application::get()->getRequest();
        $userVars = $request->getUserVars();
        $userVars['pubIdPlugIn'] = get_class($this);
        
        $linkActions['clearPubIdLinkActionARK'] = new LinkAction(
            'clearPubId', 
            new RemoteActionConfirmationModal(
                $request->getSession(),
                __('plugins.pubIds.ark.editor.clearObjectsARK.confirm'),
                __('common.delete'),
                $request->url(null, null, null, ['clearPubId'], null, $userVars),
                'modal_delete'
            ),
            __('plugins.pubIds.ark.editor.clearObjectsARK'),
            'delete',
            __('plugins.pubIds.ark.editor.clearObjectsARK')
        );
        
        return $linkActions;
    }

    public function getSuffixPatternsFieldNames() { return []; }
    public function getDAOFieldNames() { return ['pub-id::ark']; }
    public function getSuffixTypeOptions() { return ['random' => 'plugins.pubIds.ark.suffix.random']; }

    /**
     * Gera sufixo no formato: PREFIXOxxxx-yyyy
     */
    public function generateSuffix($customPrefix = null) {
        if (!$customPrefix) {
            $customPrefix = 'CRL';
        }
        
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

    /**
     * Obtém o ARK para uma publicação (apenas artigos)
     */
    public function getPubId($pubObject) {
        // Apenas para publicações (artigos)
        if (!is_a($pubObject, 'Publication')) {
            return null;
        }
        
        $contextId = $pubObject->getData('contextId');
        $storedId = $pubObject->getStoredPubId('ark');
        if (!empty($storedId)) return $storedId;
        
        // Verifica se ARK está habilitado para artigos
        $enabled = $this->getSetting($contextId, 'enablePublicationARK');
        if (!$enabled) return null;
        
        $prefix = $this->getSetting($contextId, 'arkPrefix');
        $customPrefix = $this->getSetting($contextId, 'arkCustomPrefix');
        
        if (empty($prefix)) return null;
        if (empty($customPrefix)) $customPrefix = 'CRL';
        
        // Gera sufixo único
        $maxAttempts = 10;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $suffix = $this->generateSuffix($customPrefix);
            $fullArk = $this->constructPubId($prefix, $suffix, $contextId);
            
            if ($this->checkDuplicate($fullArk, $pubObject, $contextId)) {
                return $fullArk;
            }
        }
        
        error_log("ARKPubIdPlugin: Não foi possível gerar ARK único após {$maxAttempts} tentativas");
        return null;
    }

    /**
     * Verifica se um ARK já existe na base
     * @return bool true se NÃO houver duplicata
     */
    public function checkDuplicate($pubId, $pubObject, $contextId) {
        $pubObjectId = $pubObject ? $pubObject->getId() : null;
        
        $sql = 'SELECT COUNT(*) as count FROM publication_settings ps 
                INNER JOIN publications p ON ps.publication_id = p.publication_id
                INNER JOIN submissions s ON p.submission_id = s.submission_id 
                WHERE s.context_id = ? 
                AND ps.setting_name = ? 
                AND ps.setting_value = ?';
        $params = [$contextId, 'pub-id::ark', $pubId];
        
        if ($pubObjectId) {
            $sql .= ' AND p.publication_id != ?';
            $params[] = $pubObjectId;
        }
        
        $result = DB::selectOne($sql, $params);
        return $result->count == 0;
    }

    public function getNotUniqueErrorMsg() { 
        return __('plugins.pubIds.ark.editor.arkSuffixNotUnique'); 
    }

    public function isObjectTypeEnabled($pubObjectType, $contextId) { 
        if ($pubObjectType == 'Publication') {
            return (bool) $this->getSetting($contextId, 'enablePublicationARK');
        }
        return false;
    }

    public function modifyObjectProperties($hookName, $args) { 
        $props = &$args[0]; 
        $props[] = 'pub-id::ark'; 
    }

    public function modifyObjectPropertyValues($hookName, $args) {
        $values = &$args[0]; 
        $object = $args[1]; 
        $props = $args[2];
        
        if (in_array('pub-id::ark', $props) && is_a($object, 'Publication')) { 
            $pubId = $this->getPubId($object); 
            $values['pub-id::ark'] = $pubId ? $pubId : null; 
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
        
        if (!$this->checkDuplicate($arkFull, $publication, $contextId)) {
            $errors['pub-id::ark'][] = $this->getNotUniqueErrorMsg();
        }
    }

    /**
     * Adiciona o campo ARK no formulário de publicação
     */
    public function addPublicationFormFields($hookName, $form)
    {
        // Verifica se é o formulário correto
        if ($form->id !== 'publication' && $form->id !== 'publicationIdentifiers') {
            return;
        }
        
        $contextId = $form->submissionContext->getId();
        $enabled = $this->getSetting($contextId, 'enablePublicationARK');
        
        if (!$enabled) return;
        
        $prefix = $this->getSetting($contextId, 'arkPrefix');
        $existingArk = $form->publication->getData('pub-id::ark');
        
        // Campo ARK personalizado
        $arkField = new FieldText('pub-id::ark', [
            'label' => __('plugins.pubIds.ark.displayName'),
            'value' => $existingArk,
            'isMultilingual' => false,
            'groupId' => 'identifiers',
            'help' => __('plugins.pubIds.ark.editor.arkHelp', ['prefix' => $prefix]),
        ]);
        
        $form->addField($arkField);
    }

    public function loadArkFieldComponent($hookName, $args)
    {
        $templateMgr = $args[0];
        $request = Application::get()->getRequest();
        
        $router = $request->getRouter();
        if (!is_a($router, 'PKP\core\PKPPageRouter')) {
            return;
        }

        $context = $request->getContext();
        if (!$context) return;

        $enabled = $this->getSetting($context->getId(), 'enablePublicationARK');
        if (!$enabled) return;
        
        $prefix = $this->getSetting($context->getId(), 'arkPrefix');
        $customPrefix = $this->getSetting($context->getId(), 'arkCustomPrefix');
        
        // Carrega o JavaScript do botão "Gerar ARK"
        $templateMgr->addJavaScript(
            'ark-field-injector-vars',
            'window.arkPluginConfig = { 
                prefix: "' . $prefix . '", 
                customPrefix: "' . ($customPrefix ?: 'CRL') . '",
                generateLabel: "' . __('plugins.pubIds.ark.editor.generateArk') . '"
            };',
            ['contexts' => 'backend', 'inline' => true]
        );

        $templateMgr->addJavaScript(
            'ark-field-injector',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/js/FieldArk.js?v=' . time(),
            ['contexts' => 'backend', 'priority' => STYLE_SEQUENCE_LAST]
        );

        // CSS para o botão e campo
        $templateMgr->addStyleSheet(
            'ark-field-injector-css',
            '
            .ark-field-wrapper {
                display: flex !important;
                align-items: center !important;
                width: 100%;
                gap: 0.5rem;
            }
            .ark-field-wrapper input {
                flex-grow: 0 !important;
                width: 400px !important;
                max-width: 100% !important;
            }
            .ark-generate-btn {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                font-size: 0.875rem !important;
                line-height: 1.25rem !important;
                font-weight: 600 !important;
                color: #d00a6c !important;
                background-color: #fff !important;
                border: 1px solid #aaa !important;
                border-radius: 4px !important;
                padding: 0.4375rem 0.75rem !important;
                margin-left: 0.5rem !important;
                height: 2.5rem !important;
                white-space: nowrap;
                cursor: pointer;
            }
            .ark-generate-btn:hover {
                background-color: #f5f5f5 !important;
                border-color: #d00a6c !important;
            }
            .ark-readonly-field {
                background-color: #f5f5f5 !important;
                color: #666 !important;
            }
            ',
            ['contexts' => 'backend', 'inline' => true]
        );
    }
}
