<?php
/**
 * @file plugins/pubIds/ark/resolver.php
 * 
 * ARK Resolver
 * 
 * @copyright (c) 2026 Lury Morais
 * @license GNU GPL v2
 */

// Previne acesso sem parâmetro
if (empty($_GET['ark']) && empty($_GET['id'])) {
    http_response_code(400);
    echo 'Uso: resolver.php?ark=CRL1234-ABCD';
    exit;
}

$arkSuffix = $_GET['ark'] ?? $_GET['id'];

// Limpa o sufixo (remove prefixo se veio completo)
$arkSuffix = preg_replace('/^ark:[0-9]+\//', '', $arkSuffix);
$arkSuffix = preg_replace('/^[A-Z]+\//', '', $arkSuffix);

// Carrega configuração do banco diretamente
$configFile = dirname(__FILE__, 4) . '/config.inc.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    exit('Config file not found');
}

$configLines = file($configFile, FILE_IGNORE_NEW_LINES);
$dbConfig = [];
$inDbSection = false;

foreach ($configLines as $line) {
    $line = trim($line);
    if (strpos($line, '[database]') === 0) {
        $inDbSection = true;
        continue;
    }
    if ($inDbSection && strpos($line, '[') === 0) {
        $inDbSection = false;
        continue;
    }
    if ($inDbSection && strpos($line, '=') !== false && strpos($line, ';') !== 0) {
        list($key, $value) = explode('=', $line, 2);
        $dbConfig[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }
}

// Tenta diferentes formatos (com ou sem hífen)
$tentativas = [$arkSuffix];
if (strpos($arkSuffix, '-') === false && strlen($arkSuffix) >= 8) {
    // CRL1234ABCD → CRL1234-ABCD
    $tentativas[] = substr($arkSuffix, 0, -4) . '-' . substr($arkSuffix, -4);
}

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $resultado = null;
    
    foreach ($tentativas as $sufixo) {
        // Busca em publication_settings
        $stmt = $pdo->prepare("
            SELECT ps.publication_id, s.context_id
            FROM publication_settings ps
            JOIN publications p ON ps.publication_id = p.publication_id
            JOIN submissions s ON p.submission_id = s.submission_id
            WHERE ps.setting_name = 'pub-id::ark' 
            AND (ps.setting_value = ? OR ps.setting_value LIKE CONCAT('%', ?))
            LIMIT 1
        ");
        $stmt->execute([$sufixo, $sufixo]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $resultado = $row;
            break;
        }
    }
    
    if (!$resultado) {
        // Calcula a URL base do site
        $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
        $siteUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$baseUrl";
        
        http_response_code(404);
        echo '<!DOCTYPE html><html><head><title>ARK Not Found</title></head>';
        echo '<body style="font-family: sans-serif; padding: 20px;">';
        echo '<h1>ARK Not Found</h1>';
        echo '<p>O identificador <strong>' . htmlspecialchars($arkSuffix) . '</strong> não foi encontrado.</p>';
        echo '<hr><small>ARK Resolver Plugin by Lury Morais 2026</small><br><br>';
        echo '<a href="' . htmlspecialchars($siteUrl) . '">Homepage</a>';
        echo '</body></html>';
        exit;
    }
    
    // Busca o path do periódico
    $stmt2 = $pdo->prepare("SELECT path FROM journals WHERE journal_id = ?");
    $stmt2->execute([$resultado['context_id']]);
    $journal = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if (!$journal) {
        http_response_code(500);
        exit('Journal not found');
    }
    
    // Busca URL amigável
    $stmt3 = $pdo->prepare("
        SELECT setting_value FROM publication_settings 
        WHERE publication_id = ? AND setting_name = 'urlPath'
        LIMIT 1
    ");
    $stmt3->execute([$resultado['publication_id']]);
    $urlPath = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    // Detecta idioma pela preferência do navegador
    $locale = 'pt_BR';
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if ($lang === 'en') $locale = 'en';
        elseif ($lang === 'es') $locale = 'es';
    }
    
    // Monta URL base
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
    
    // Monta URL de redirecionamento
    if ($urlPath && !empty($urlPath['setting_value'])) {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/{$locale}/article/view/{$urlPath['setting_value']}";
    } else {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/{$locale}/article/view/{$resultado['publication_id']}";
    }
    
    // Redireciona
    header('HTTP/1.1 302 Found');
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (PDOException $e) {
    error_log("ARK Resolver PDO Error: " . $e->getMessage());
    http_response_code(500);
    echo '<h1>Database Error</h1>';
    echo '<p>Erro ao conectar ao banco de dados.</p>';
    exit;
} catch (Exception $e) {
    error_log("ARK Resolver Error: " . $e->getMessage());
    http_response_code(500);
    echo '<h1>Internal Error</h1>';
    echo '<p>Ocorreu um erro interno no resolvedor.</p>';
    exit;
}
