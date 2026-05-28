<?php

/**
 * @file plugins/pubIds/ark/ArkPageHandler.php
 */

namespace APP\plugins\pubIds\ark;

use PKP\handler\PKPHandler;
use PKP\security\Role;
use APP\core\Application;

class ArkPageHandler extends PKPHandler
{
    public $plugin;
    
    public function __construct(ARKPubIdPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
        
        $this->addRoleAssignment(
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_AUTHOR,
                Role::ROLE_ID_READER,
            ],
            ['saveArk']
        );
    }
    
    public function authorize($request, &$args, $roleAssignments)
    {
        return true;
    }
    
    public function saveArk($args, $request)
    {
        if ($request->getRequestMethod() !== 'POST') {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'msg' => 'Method not allowed']);
            exit;
        }
        
        $issueId = (int) $request->getUserVar('issueId');
        $arkValue = $request->getUserVar('arkValue');
        
        if (!$issueId || !$arkValue) {
            header('Content-Type: application/json');
            echo json_encode(['status' => false, 'msg' => 'Missing parameters']);
            exit;
        }
        
        $success = $this->plugin->saveArk($issueId, $arkValue);
        
        header('Content-Type: application/json');
        echo json_encode(['status' => $success, 'issueId' => $issueId, 'ark' => $arkValue]);
        exit;
    }
}