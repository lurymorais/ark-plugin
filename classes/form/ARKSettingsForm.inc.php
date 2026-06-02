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
        
        $arkPrefix = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'arkPrefix')
            ->where('locale', '')
            ->value('setting_value');
        
        $telemetryLevel = DB::table('journal_settings')
            ->where('journal_id', $contextId)
            ->where('setting_name', 'telemetryLevel')
            ->where('locale', '')
            ->value('setting_value');
        
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

    public function handleTokenRecovery($request)
    {
        $plugin = $this->_getPlugin();
        $contextId = $this->_getContextId();
        
        $lastAttempt = $plugin->getSetting($contextId, 'lastRecoveryAttempt');
        if ($lastAttempt && (time() - $lastAttempt) < 3600) {
            $timeRemaining = 3600 - (time() - $lastAttempt);
            $minutes = ceil($timeRemaining / 60);
            return [
                'success' => false,
                'message' => __('plugins.pubIds.ark.recovery.rateLimit', ['minutes' => $minutes])
            ];
        }
        
        $plugin->updateSetting($contextId, 'lastRecoveryAttempt', time());
        $result = $plugin->requestTokenRecovery($contextId);
        
        return $result;
    }

    public function execute(...$functionArgs) {
        // ============================================
        // DEBUG INICIADO
        // ============================================
        error_log("[ARK_FORM] ========== INICIANDO EXECUTE ==========");
        
        $contextId = $this->_getContextId();
        $plugin = $this->_getPlugin();
        $naan = $this->getData('arkPrefix');
        
        error_log("[ARK_FORM] Context ID: {$contextId}");
        error_log("[ARK_FORM] NAAN recebido: " . ($naan ?: 'VAZIO'));
        
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        
        if (!$context || $context->getId() != $contextId) {
            $contextDao = Application::getContextDAO();
            $context = $contextDao->getById($contextId);
        }
        
        $telemetryData = [];
        $telemetryLevel = $this->getData('telemetryLevel');
        error_log("[ARK_FORM] Telemetry Level: " . ($telemetryLevel ?: 'restricted'));
        
        if ($telemetryLevel === 'public' && $context) {
            $journalName = $context->getData('name');
            if (is_array($journalName)) {
                $primaryLocale = $context->getPrimaryLocale();
                $journalName = $journalName[$primaryLocale] ?? reset($journalName);
            }
            if (is_array($journalName)) {
                $journalName = implode(', ', $journalName);
            }
            
            $country = $context->getData('country');
            if (is_array($country)) {
                $primaryLocale = $context->getPrimaryLocale();
                $country = $country[$primaryLocale] ?? reset($country);
            }
            
            $telemetryData = [
                'journal_name' => $journalName,
                'country' => $country,
                'email' => $context->getData('contactEmail'),
                'primary_language' => $context->getPrimaryLocale()
            ];
            error_log("[ARK_FORM] Dados públicos coletados: " . json_encode($telemetryData));
        }

        // ============================================
        // VALIDAÇÃO DO NAAN - DESATIVADA TEMPORARIAMENTE
        // ============================================
        error_log("[ARK_FORM] Iniciando validação do NAAN...");
        $validation = $plugin->validateNaanWithTelemetry($naan, $contextId, $telemetryData);
        error_log("[ARK_FORM] Validação resultado: " . ($validation['valid'] ? 'APROVADO' : 'REPROVADO'));

        if (!$validation['valid']) {
            error_log("[ARK_FORM] ERRO de validação: " . ($validation['message'] ?? 'sem mensagem'));
            $this->addError('arkPrefix', $validation['message']);
            return false;
        }
        // ============================================
        // SALVANDO CONFIGURAÇÕES
        // ============================================
        $enablePublication = $this->getData('enablePublicationARK');
        $enableIssue = $this->getData('enableIssueARK');
        $arkSuffix = $this->getData('arkSuffix');
        $arkCustomPrefix = $this->getData('arkCustomPrefix');
        $resolverType = $this->getData('resolverType');
        $arkResolver = $this->getData('arkResolver');
        $arkImplementationDate = $this->getData('arkImplementationDate');
        $telemetryLevel = $this->getData('telemetryLevel');

        $token = $plugin->getPluginToken($contextId);
        $adminSecret = $plugin->getSetting($contextId, 'ark_admin_secret');
        if (empty($adminSecret)) {
            $adminSecret = bin2hex(random_bytes(32));
            $plugin->updateSetting($contextId, 'ark_admin_secret', $adminSecret);
        }
        
        $apiEndpoint = $request->getBaseUrl() . '/index.php/' . $context->getPath() . '/ark-api/telemetry';
        error_log("[ARK_FORM] API Endpoint: " . $apiEndpoint);
        
        try {
            // ============================================
            // SALVANDO NO journal_settings
            // ============================================
            error_log("[ARK_FORM] Salvando no journal_settings...");
            
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
            }
            
            error_log("[ARK_FORM] journal_settings salvo com sucesso!");
            
            // ============================================
            // SALVANDO NA ark_journals
            // ============================================
            error_log("[ARK_FORM] Salvando na tabela ark_journals...");
            
            $journalNameForDb = $context->getData('name');
            if (is_array($journalNameForDb)) {
                $primaryLocale = $context->getPrimaryLocale();
                $journalNameForDb = $journalNameForDb[$primaryLocale] ?? reset($journalNameForDb);
            }
            if (is_array($journalNameForDb)) {
                $journalNameForDb = implode(', ', $journalNameForDb);
            }
            
            $countryForDb = $context->getData('country');
            if (is_array($countryForDb)) {
                $primaryLocale = $context->getPrimaryLocale();
                $countryForDb = $countryForDb[$primaryLocale] ?? reset($countryForDb);
            }
            
            $scheduledDay = rand(1, 28);
            $nextPullDate = date('Y-m-d', strtotime('+' . ($scheduledDay - date('j')) . ' days'));
            
            DB::table('ark_journals')->updateOrInsert(
                ['naan' => $naan],
                [
                    'plugin_token' => $token,
                    'admin_secret' => $adminSecret,
                    'journal_url' => $request->getBaseUrl(),
                    'journal_name' => $journalNameForDb,
                    'country' => $countryForDb,
                    'email' => $context->getData('contactEmail'),
                    'primary_language' => $context->getPrimaryLocale(),
                    'telemetry_level' => $telemetryLevel,
                    'arks_count' => $plugin->getTotalArksCount($contextId),
                    'plugin_version' => $plugin->getPluginVersion(),
                    'api_endpoint' => $apiEndpoint,
                    'status' => 'active',
                    'last_sync' => date('Y-m-d H:i:s'),
                    'sync_attempts' => 0,
                    'error_message' => null,
                    'scheduled_day' => $scheduledDay,
                    'next_pull' => $nextPullDate,
                    'last_pull' => null,
                    'pull_interval' => 'monthly'
                ]
            );
            
            error_log("[ARK_FORM] ark_journals salvo com sucesso!");
            
            // ============================================
            // REGISTRANDO LOG
            // ============================================
            DB::table('ark_sync_log')->insert([
                'naan' => $naan,
                'action' => 'register',
                'status' => 'success',
                'message' => 'Journal registered with telemetry_level: ' . $telemetryLevel
            ]);
            
            error_log("[ARK_FORM] Log registrado com sucesso!");
            
            // ============================================
            // ENVIANDO TELEMETRIA
            // ============================================
            $plugin->sendTelemetryData($contextId, $telemetryLevel);
            
            error_log("[ARK_FORM] ========== EXECUTE FINALIZADO COM SUCESSO ==========");
            
        } catch (Exception $e) {
            error_log("[ARK_FORM] ERRO CRÍTICO: " . $e->getMessage());
            error_log("[ARK_FORM] Stack trace: " . $e->getTraceAsString());
            $this->addError('form', 'Error saving settings: ' . $e->getMessage());
            return false;
        }
        
        parent::execute(...$functionArgs);
        
        return true;
    }
}