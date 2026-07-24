<?php
/**
 * @file plugins/pubIds/ark/save_ajax.php
 * @brief AJAX endpoint to save ARK for issues
 */

// ========== DEFINE CONSTANTS ==========
$baseDir = dirname(__FILE__, 4);

if (!defined('INDEX_FILE_LOCATION')) {
    define('INDEX_FILE_LOCATION', $baseDir . '/index.php');
}

// ========== CHANGE TO OJS ROOT ==========
chdir($baseDir);

// ========== LOAD OJS BOOTSTRAP ==========
require_once $baseDir . '/lib/pkp/includes/bootstrap.php';

use APP\core\Application;
use PKP\security\Role;

header('Content-Type: application/json');

// ========== GET USER VIA OJS API ==========
$user = Application::get()->getRequest()->getUser();

if (!$user) {
    http_response_code(401);
    echo json_encode(['status' => false, 'error' => 'Authentication required']);
    exit;
}

// ========== CHECK USER ROLE ==========
$request = Application::get()->getRequest();
$context = $request->getContext();
$contextId = $context ? $context->getId() : 0;

$userRoles = $user->getRoles($contextId);
$allowedRoles = [
    Role::ROLE_ID_SITE_ADMIN,
    Role::ROLE_ID_MANAGER,
    Role::ROLE_ID_SUB_EDITOR,
    Role::ROLE_ID_ASSISTANT
];

$hasAllowedRole = false;
foreach ($userRoles as $role) {
    if (in_array($role->getId(), $allowedRoles)) {
        $hasAllowedRole = true;
        break;
    }
}

if (!$hasAllowedRole) {
    http_response_code(403);
    echo json_encode(['status' => false, 'error' => 'Insufficient permissions']);
    exit;
}

// ========== CSRF PROTECTION ==========
$csrfToken = isset($_SERVER['HTTP_X_CSRF_TOKEN']) ? $_SERVER['HTTP_X_CSRF_TOKEN'] : null;
if (!$csrfToken) {
    $csrfToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
}

if (!$csrfToken || !$request->checkCSRF($csrfToken)) {
    http_response_code(403);
    echo json_encode(['status' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'OK']);
    exit;
}

// ========== DATABASE CONFIGURATION ==========
$configFile = $baseDir . '/config.inc.php';

if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => 'Config file not found']);
    exit;
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
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => 'Database config incomplete']);
    exit;
}

$driver = isset($dbConfig['driver']) ? $dbConfig['driver'] : 'mysql';
$charset = isset($dbConfig['charset']) ? $dbConfig['charset'] : 'utf8';

if ($driver === 'postgres' || $driver === 'postgresql') {
    $dsn = "pgsql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
} elseif ($driver === 'mysqli' || $driver === 'mysql') {
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset={$charset}";
} else {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => "Unsupported database driver: {$driver}"]);
    exit;
}

// ========== CONNECT TO DATABASE ==========
try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => 'Database connection failed']);
    exit;
}

/**
 * Check if an ARK is already in use (globally)
 */
function isArkDuplicate($pdo, $arkValue, $currentIssueId = null) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM publication_settings 
        WHERE setting_name = 'pub-id::ark' AND setting_value = ?
    ");
    $stmt->execute([$arkValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return true;
    }
    
    $sql = "
        SELECT COUNT(*) as count FROM issue_settings 
        WHERE setting_name = 'pub-id::ark' AND setting_value = ?
    ";
    $params = [$arkValue];
    
    if ($currentIssueId) {
        $sql .= " AND issue_id != ?";
        $params[] = $currentIssueId;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] > 0;
}

// Check if this is a request to fetch existing ARK
if (isset($_GET['check']) && $_GET['check'] == 1 && isset($_GET['issueId'])) {
    $issueId = (int)$_GET['issueId'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT setting_value FROM issue_settings 
            WHERE issue_id = ? AND setting_name = 'pub-id::ark'
            LIMIT 1
        ");
        $stmt->execute([$issueId]);
        $ark = $stmt->fetchColumn();
        
        echo json_encode([
            'exists' => !empty($ark),
            'ark' => $ark
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['exists' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Check if this is a request to validate article ARK duplicate
if (isset($_GET['check_article']) && $_GET['check_article'] == 1 && isset($_GET['publicationId']) && isset($_GET['ark'])) {
    $publicationId = (int)$_GET['publicationId'];
    $arkValue = $_GET['ark'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM publication_settings 
            WHERE setting_name = 'pub-id::ark' 
            AND setting_value = ?
            AND publication_id != ?
        ");
        $stmt->execute([$arkValue, $publicationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $duplicateInArticles = ($result['count'] > 0);
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM issue_settings 
            WHERE setting_name = 'pub-id::ark' 
            AND setting_value = ?
        ");
        $stmt->execute([$arkValue]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $duplicateInIssues = ($result['count'] > 0);
        
        echo json_encode([
            'duplicate' => ($duplicateInArticles || $duplicateInIssues),
            'publicationId' => $publicationId,
            'ark' => $arkValue
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['duplicate' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ========== PROCESS SAVE REQUEST ==========
$issueId = 0;
$arkValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issueId = isset($_POST['issueId']) ? (int)$_POST['issueId'] : 0;
    $arkValue = isset($_POST['arkValue']) ? $_POST['arkValue'] : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $issueId = isset($_GET['issueId']) ? (int)$_GET['issueId'] : 0;
    $arkValue = isset($_GET['arkValue']) ? $_GET['arkValue'] : '';
} else {
    http_response_code(405);
    echo json_encode(['status' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!$issueId || empty($arkValue)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => 'Missing parameters']);
    exit;
}

if (!preg_match('/^ark:\d+[A-Za-z0-9\/\-_]+$/', $arkValue)) {
    http_response_code(400);
    echo json_encode(['status' => false, 'error' => 'Invalid ARK format']);
    exit;
}

try {
    if (isArkDuplicate($pdo, $arkValue, $issueId)) {
        echo json_encode([
            'status' => false,
            'error' => 'Duplicate ARK detected. This ARK is already in use by another article or issue.',
            'error_code' => 'DUPLICATE_ARK',
            'ark' => $arkValue
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM issue_settings 
        WHERE issue_id = ? AND setting_name = 'pub-id::ark' AND locale = ''
    ");
    $stmt->execute([$issueId]);
    $exists = $stmt->fetchColumn() > 0;
    
    if ($exists) {
        $stmt = $pdo->prepare("
            UPDATE issue_settings 
            SET setting_value = ? 
            WHERE issue_id = ? AND setting_name = 'pub-id::ark' AND locale = ''
        ");
        $result = $stmt->execute([$arkValue, $issueId]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO issue_settings (issue_id, setting_name, locale, setting_value) 
            VALUES (?, 'pub-id::ark', '', ?)
        ");
        $result = $stmt->execute([$issueId, $arkValue]);
    }
    
    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'ARK saved successfully',
            'issueId' => $issueId,
            'ark' => $arkValue
        ]);
    } else {
        echo json_encode(['status' => false, 'error' => 'Database update failed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => 'PDO Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => false, 'error' => 'General Error: ' . $e->getMessage()]);
}