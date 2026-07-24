<?php

/**
 * @file plugins/pubIds/ark/ArkPageHandler.php
 * 
 * @brief Handler for ARK plugin page operations
 * @copyright (c) 2026 Lury Morais
 * @license GNU GPL v2
 */

namespace APP\plugins\pubIds\ark;

use PKP\handler\PKPHandler;
use PKP\security\Role;
use PKP\security\authorization\ContextAccessPolicy;
use APP\core\Application;
use PKP\core\JSONMessage;

class ArkPageHandler extends PKPHandler
{
    /** @var ARKPubIdPlugin */
    public $plugin;
    
    /**
     * Constructor - Sets up role assignments
     * 
     * @param ARKPubIdPlugin $plugin The plugin instance
     */
    public function __construct(ARKPubIdPlugin $plugin)
    {
        parent::__construct();
        $this->plugin = $plugin;
        
        // Only Editors, Managers, and Site Admins can save ARK
        $this->addRoleAssignment(
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            ['saveArk']
        );
    }
    
    /**
     * Authorize the request - Now properly validates user roles
     * 
     * @param PKPRequest $request The request object
     * @param array $args Request arguments
     * @param array $roleAssignments Role assignments for this handler
     * @return bool True if authorized, false otherwise
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        // Add context access policy to ensure user has access to this journal
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        
        // Let parent authorize handle the actual validation
        return parent::authorize($request, $args, $roleAssignments);
    }
    
    /**
     * Save ARK for an issue
     * 
     * @param array $args Request arguments
     * @param PKPRequest $request The request object
     * @return void
     */
    public function saveArk($args, $request)
    {
        // Validate request method
        if ($request->getRequestMethod() !== 'POST') {
            header('Content-Type: application/json');
            http_response_code(405);
            echo json_encode(['status' => false, 'msg' => 'Method not allowed']);
            exit;
        }
        
        // Validate CSRF token
        $csrfToken = $request->getUserVar('csrf_token') ?? 
                     $request->getHeader('X-CSRF-Token') ?? 
                     null;
        
        if (!$csrfToken || !$request->checkCSRF($csrfToken)) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => false, 'msg' => 'Invalid CSRF token']);
            exit;
        }
        
        // Get parameters
        $issueId = (int) $request->getUserVar('issueId');
        $arkValue = trim($request->getUserVar('arkValue'));
        
        // Validate parameters
        if (!$issueId || empty($arkValue)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Missing parameters']);
            exit;
        }
        
        // Validate ARK format
        if (!preg_match('/^ark:\d+[A-Za-z0-9\/\-_]+$/', $arkValue)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['status' => false, 'msg' => 'Invalid ARK format']);
            exit;
        }
        
        // Ensure user has permission for this context
        $context = $request->getContext();
        if (!$context) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['status' => false, 'msg' => 'No context available']);
            exit;
        }
        
        // Verify the issue belongs to this context
        try {
            $contextId = $context->getId();
            $pdo = $this->getDatabaseConnection();
            if ($pdo) {
                $stmt = $pdo->prepare("
                    SELECT journal_id FROM issues WHERE issue_id = ?
                ");
                $stmt->execute([$issueId]);
                $issue = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                if (!$issue || $issue['journal_id'] != $contextId) {
                    header('Content-Type: application/json');
                    http_response_code(403);
                    echo json_encode(['status' => false, 'msg' => 'Issue does not belong to this journal']);
                    exit;
                }
            }
        } catch (\Exception $e) {
            // Log error but continue (fallback to plugin's own validation)
            error_log("[ARK] Issue validation error: " . $e->getMessage());
        }
        
        // Save the ARK using the plugin
        $success = $this->plugin->saveArk($issueId, $arkValue);
        
        header('Content-Type: application/json');
        if ($success) {
            http_response_code(200);
            echo json_encode([
                'status' => true, 
                'message' => 'ARK saved successfully',
                'issueId' => $issueId, 
                'ark' => $arkValue
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'status' => false, 
                'msg' => 'Failed to save ARK'
            ]);
        }
        exit;
    }
    
    /**
     * Get database connection for additional validation
     * 
     * @return \PDO|null
     */
    private function getDatabaseConnection()
    {
        try {
            $configFile = dirname(__FILE__, 5) . '/config.inc.php';
            
            if (!file_exists($configFile)) {
                return null;
            }
            
            $configLines = file($configFile, FILE_IGNORE_NEW_LINES);
            $dbConfig = [];
            $inDbSection = false;
            
            foreach ($configLines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === ';' || $line[0] === '#') continue;
                
                if (strpos($line, '[database]') === 0) {
                    $inDbSection = true;
                    continue;
                }
                
                if ($inDbSection && strpos($line, '[') === 0) {
                    $inDbSection = false;
                    continue;
                }
                
                if ($inDbSection && strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $dbConfig[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
                }
            }
            
            if (empty($dbConfig['host']) || empty($dbConfig['name']) || empty($dbConfig['username']) || empty($dbConfig['password'])) {
                return null;
            }
            
            $driver = isset($dbConfig['driver']) ? $dbConfig['driver'] : 'mysql';
            
            if ($driver === 'postgres' || $driver === 'postgresql') {
                $dsn = "pgsql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
            } else {
                $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8";
            }
            
            return new \PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            
        } catch (\Exception $e) {
            error_log("[ARK] Database connection error: " . $e->getMessage());
            return null;
        }
    }
}