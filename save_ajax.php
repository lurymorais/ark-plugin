<?php
/**
 * @file plugins/pubIds/ark/save_ajax.php
 * @brief AJAX endpoint to save ARK for issues
 * 
 * Copyright (c) 2026 Lury Morais
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Respond to OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => true, 'message' => 'OK']);
    exit;
}

// Load database configuration from OJS config.inc.php
$configFile = dirname(__FILE__, 4) . '/config.inc.php';

if (!file_exists($configFile)) {
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

if (empty($dbConfig['host']) || empty($dbConfig['name'])) {
    echo json_encode(['status' => false, 'error' => 'Database config incomplete']);
    exit;
}

$dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8";

/**
 * Check if an ARK is already in use (globally)
 * 
 * @param PDO $pdo Database connection
 * @param string $arkValue ARK to check
 * @param int|null $currentIssueId Issue ID to exclude (for updates)
 * @return bool True if duplicate exists
 */
function isArkDuplicate($pdo, $arkValue, $currentIssueId = null) {
    // Check in publication_settings (articles)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM publication_settings 
        WHERE setting_name = 'pub-id::ark' AND setting_value = ?
    ");
    $stmt->execute([$arkValue]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        return true;
    }
    
    // Check in issue_settings (issues), excluding current issue if update
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
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
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
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        // Check in publications (articles)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM publication_settings 
            WHERE setting_name = 'pub-id::ark' 
            AND setting_value = ?
            AND publication_id != ?
        ");
        $stmt->execute([$arkValue, $publicationId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $duplicateInArticles = ($result['count'] > 0);
        
        // Check in issues
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

// Process save request
$issueId = 0;
$arkValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issueId = isset($_POST['issueId']) ? (int)$_POST['issueId'] : 0;
    $arkValue = isset($_POST['arkValue']) ? $_POST['arkValue'] : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $issueId = isset($_GET['issueId']) ? (int)$_GET['issueId'] : 0;
    $arkValue = isset($_GET['arkValue']) ? $_GET['arkValue'] : '';
} else {
    echo json_encode(['status' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!$issueId || empty($arkValue)) {
    echo json_encode(['status' => false, 'error' => 'Missing parameters']);
    exit;
}

try {
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Global duplicate check
    if (isArkDuplicate($pdo, $arkValue, $issueId)) {
        echo json_encode([
            'status' => false,
            'error' => 'Duplicate ARK detected. This ARK is already in use by another article or issue.',
            'error_code' => 'DUPLICATE_ARK',
            'ark' => $arkValue
        ]);
        exit;
    }
    
    // Insert or update
    $sql = "
        INSERT INTO issue_settings (issue_id, setting_name, locale, setting_value) 
        VALUES (?, 'pub-id::ark', '', ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$issueId, $arkValue, $arkValue]);
    
    if ($result) {
        echo json_encode([
            'status' => true,
            'message' => 'ARK saved successfully',
            'issueId' => $issueId,
            'ark' => $arkValue
        ]);
    } else {
        echo json_encode(['status' => false, 'error' => 'Database insert failed']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'error' => 'PDO Error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['status' => false, 'error' => 'General Error: ' . $e->getMessage()]);
}