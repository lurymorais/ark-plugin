<?php

/**
 * @file plugins/pubIds/ark/classes/handler/ARKRecoveryHandler.inc.php
 */

use PKP\handler\PKPHandler;
use PKP\security\authorization\ContextAccessPolicy;
use PKP\core\JSONMessage;
use APP\core\Application;

class ARKRecoveryHandler extends PKPHandler
{
    /** @var ARKPubIdPlugin */
    var $plugin;
    
    function __construct()
    {
        parent::__construct();
        $this->addRoleAssignment(
            [\PKP\security\Role::ROLE_ID_MANAGER, \PKP\security\Role::ROLE_ID_SITE_ADMIN],
            ['recoverToken']
        );
    }
    
    function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }
    
    function recoverToken($args, $request)
    {
        // Log the request
        error_log("[ARK Recovery] === RECOVERY REQUEST STARTED ===");
        error_log("[ARK Recovery] User: " . ($request->getUser() ? $request->getUser()->getUsername() : 'Not logged'));
        
        $plugin = \PKP\plugins\PluginRegistry::getPlugin('pubIds', 'ark');
        if (!$plugin) {
            error_log("[ARK Recovery] ERROR: Plugin not found");
            return new JSONMessage(false, __('plugins.pubIds.ark.recovery.error.pluginNotFound'));
        }
        
        $context = $request->getContext();
        if (!$context) {
            error_log("[ARK Recovery] ERROR: No context found");
            return new JSONMessage(false, __('plugins.pubIds.ark.recovery.error.noContext'));
        }
        
        $contextId = $context->getId();
        $naan = $plugin->getSetting($contextId, 'arkPrefix');
        
        error_log("[ARK Recovery] NAAN configured: " . ($naan ? $naan : 'NOT SET'));
        
        if (empty($naan)) {
            error_log("[ARK Recovery] ERROR: NAAN not configured");
            return new JSONMessage(false, __('plugins.pubIds.ark.recovery.noNaan'));
        }
        
        $settingsForm = $plugin->instantiateSettingsForm($contextId);
        $result = $settingsForm->handleTokenRecovery($request);
        
        error_log("[ARK Recovery] Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED'));
        error_log("[ARK Recovery] Message: " . $result['message']);
        
        if ($result['success']) {
            return new JSONMessage(true, $result['message']);
        } else {
            return new JSONMessage(false, $result['message']);
        }
    }
}