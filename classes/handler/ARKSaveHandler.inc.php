<?php
/**
 * @file plugins/pubIds/ark/classes/handler/ARKSaveHandler.inc.php
 *
 * @brief Handler for AJAX save operations
 * @copyright (c) 2026 Lury Morais
 * @license GNU GPL v2
 */

use PKP\handler\PKPHandler;
use PKP\security\Role;
use APP\core\Application;
use Illuminate\Support\Facades\DB;

class ARKSaveHandler extends PKPHandler
{
    /** @var ARKPubIdPlugin */
    public $plugin;

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
    }

    public function __construct()
    {
        parent::__construct();
        
        $this->addRoleAssignment(
            [
                Role::ROLE_ID_SITE_ADMIN,
                Role::ROLE_ID_MANAGER,
                Role::ROLE_ID_SUB_EDITOR,
                Role::ROLE_ID_ASSISTANT,
            ],
            ['saveArk', 'checkArk', 'checkArticleArk']
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        $user = $request->getUser();
        if (!$user) {
            return false;
        }
        
        $context = $request->getContext();
        $contextId = $context ? $context->getId() : 0;
        
        $userRoles = $user->getRoles($contextId);
        $allowedRoles = [
            Role::ROLE_ID_SITE_ADMIN,
            Role::ROLE_ID_MANAGER,
            Role::ROLE_ID_SUB_EDITOR,
            Role::ROLE_ID_ASSISTANT
        ];
        
        foreach ($userRoles as $role) {
            if (in_array($role->getId(), $allowedRoles)) {
                return true;
            }
        }
        
        return false;
    }

    private function sendJsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function saveArk($args, $request)
    {
        if (!$this->authorize($request, $args, [])) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Unauthorized'], 401);
        }
        
        if ($request->getRequestMethod() !== 'POST') {
            $this->sendJsonResponse(['status' => false, 'error' => 'Method not allowed'], 405);
        }

        $issueId = (int) $request->getUserVar('issueId');
        $arkValue = trim($request->getUserVar('arkValue'));

        if (!$issueId || empty($arkValue)) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Missing parameters'], 400);
        }

        if (!preg_match('/^ark:\d+[A-Za-z0-9\/\-_]+$/', $arkValue)) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Invalid ARK format'], 400);
        }

        try {
            // Check for duplicates
            $duplicateInArticles = DB::table('publication_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->exists();

            $duplicateInIssues = DB::table('issue_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->where('issue_id', '!=', $issueId)
                ->exists();

            if ($duplicateInArticles || $duplicateInIssues) {
                $this->sendJsonResponse([
                    'status' => false,
                    'error' => 'Duplicate ARK detected.',
                    'error_code' => 'DUPLICATE_ARK',
                    'ark' => $arkValue
                ], 400);
            }

            // Check if record exists
            $exists = DB::table('issue_settings')
                ->where('issue_id', $issueId)
                ->where('setting_name', 'pub-id::ark')
                ->where('locale', '')
                ->exists();

            if ($exists) {
                DB::table('issue_settings')
                    ->where('issue_id', $issueId)
                    ->where('setting_name', 'pub-id::ark')
                    ->where('locale', '')
                    ->update(['setting_value' => $arkValue]);
            } else {
                DB::table('issue_settings')->insert([
                    'issue_id' => $issueId,
                    'setting_name' => 'pub-id::ark',
                    'locale' => '',
                    'setting_value' => $arkValue
                ]);
            }

            $this->sendJsonResponse([
                'status' => true,
                'message' => 'ARK saved successfully',
                'issueId' => $issueId,
                'ark' => $arkValue
            ], 200);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'status' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkArk($args, $request)
    {
        if (!$this->authorize($request, $args, [])) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $issueId = (int) $request->getUserVar('issueId');

        if (!$issueId) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Missing issueId'], 400);
        }

        try {
            $ark = DB::table('issue_settings')
                ->where('issue_id', $issueId)
                ->where('setting_name', 'pub-id::ark')
                ->where('locale', '')
                ->value('setting_value');

            $this->sendJsonResponse([
                'exists' => !empty($ark),
                'ark' => $ark
            ], 200);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'status' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkArticleArk($args, $request)
    {
        if (!$this->authorize($request, $args, [])) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Unauthorized'], 401);
        }
        
        $publicationId = (int) $request->getUserVar('publicationId');
        $arkValue = trim($request->getUserVar('ark'));

        if (!$publicationId || empty($arkValue)) {
            $this->sendJsonResponse(['status' => false, 'error' => 'Missing parameters'], 400);
        }

        try {
            $duplicateInArticles = DB::table('publication_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->where('publication_id', '!=', $publicationId)
                ->exists();

            $duplicateInIssues = DB::table('issue_settings')
                ->where('setting_name', 'pub-id::ark')
                ->where('setting_value', $arkValue)
                ->exists();

            $this->sendJsonResponse([
                'duplicate' => ($duplicateInArticles || $duplicateInIssues),
                'publicationId' => $publicationId,
                'ark' => $arkValue
            ], 200);

        } catch (Exception $e) {
            $this->sendJsonResponse([
                'status' => false,
                'error' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }
}