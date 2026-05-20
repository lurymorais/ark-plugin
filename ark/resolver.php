<?php
/**
 * @file plugins/pubIds/ark/resolver.php
 * 
 * ARK Resolver
 * 
 * @copyright (c) 2026 Lury Morais
 * @license GNU GPL v2
 */

// BLOCK SEARCH ENGINES - Must be before any output
header('X-Robots-Tag: noindex, nofollow');

function getSiteBaseUrl() {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $baseUrl;
}

function showErrorPage($statusCode, $titleEn, $titlePt, $messageEn, $messagePt, $detailsEn = '', $detailsPt = '') {
    $siteUrl = getSiteBaseUrl();
    $pluginUrl = 'https://github.com/lurymorais/ark-plugin';
    
    http_response_code($statusCode);
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($titleEn) . ' / ' . htmlspecialchars($titlePt) . ' - ARK Resolver</title>
        <style>
            /* Light mode (default) */
            :root {
                --bg-color: #ffffff;
                --text-color: #333333;
                --border-color: #e0e0e0;
                --section-bg-pt: #f8f9fa;
                --section-bg-en: #ffffff;
                --link-color: #0366d6;
                --divider-color: #cccccc;
                --example-bg: #f0f0f0;
            }
            
            /* Dark mode (automatic based on system preference) */
            @media (prefers-color-scheme: dark) {
                :root {
                    --bg-color: #1a1a2e;
                    --text-color: #e0e0e0;
                    --border-color: #2d2d44;
                    --section-bg-pt: #16213e;
                    --section-bg-en: #1a1a2e;
                    --link-color: #58a6ff;
                    --divider-color: #2d2d44;
                    --example-bg: #0f0f1a;
                }
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                max-width: 900px;
                margin: 40px auto;
                padding: 20px;
                line-height: 1.6;
                background-color: var(--bg-color);
                color: var(--text-color);
            }
            h1 { font-size: 1.8rem; }
            hr { margin: 30px 0; border: none; border-top: 1px solid var(--divider-color); }
            .divider {
                margin: 30px 0;
                text-align: center;
                color: var(--divider-color);
            }
            .language-section {
                margin: 20px 0;
                padding: 20px;
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }
            .language-pt { background-color: var(--section-bg-pt); }
            .language-en { background-color: var(--section-bg-en); }
            .language-label {
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: var(--text-color);
                opacity: 0.7;
                margin-bottom: 10px;
            }
            a { color: var(--link-color); text-decoration: none; }
            a:hover { text-decoration: underline; }
            .identifier {
                font-family: monospace;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <h1>ARK Resolver</h1>
        
        <div class="language-section language-pt">
            <div class="language-label">PORTUGUÊS</div>
            <h2>' . htmlspecialchars($titlePt) . '</h2>
            <p>' . str_replace('<strong>', '<span class="identifier">', str_replace('</strong>', '</span>', htmlspecialchars($messagePt))) . '</p>';
    
    if ($detailsPt) {
        echo '<p><strong>Detalhe:</strong> ' . htmlspecialchars($detailsPt) . '</p>';
    }
    
    echo '
            <p><a href="' . htmlspecialchars($siteUrl) . '">Voltar para a página inicial</a></p>
        </div>
        
        <div class="divider">─────────────────────────────────</div>
        
        <div class="language-section language-en">
            <div class="language-label">ENGLISH</div>
            <h2>' . htmlspecialchars($titleEn) . '</h2>
            <p>' . str_replace('<strong>', '<span class="identifier">', str_replace('</strong>', '</span>', htmlspecialchars($messageEn))) . '</p>';
    
    if ($detailsEn) {
        echo '<p><strong>Details:</strong> ' . htmlspecialchars($detailsEn) . '</p>';
    }
    
    echo '
            <p><a href="' . htmlspecialchars($siteUrl) . '">Back to homepage</a></p>
        </div>
        
        <hr>
        <p style="text-align: center;">
            <small>
                <a href="' . $pluginUrl . '">ARK Plugin on GitHub</a> | 
                <a href="https://n2t.net/">n2t.net</a>
            </small>
        </p>
    </body>
    </html>';
    exit;
}

// Check parameter
if (empty($_GET['ark']) && empty($_GET['id'])) {
    showErrorPage(
        400,
        'Missing Parameter',
        'Parâmetro Ausente',
        'No ARK identifier was provided.',
        'Nenhum identificador ARK foi fornecido.',
        'Expected usage: resolver.php?ark=CRL1234-ABCD or resolver.php?ark=CRL1234ABCD',
        'Uso esperado: resolver.php?ark=CRL1234-ABCD ou resolver.php?ark=CRL1234ABCD'
    );
}

$arkSuffix = $_GET['ark'] ?? $_GET['id'];
$originalInput = $arkSuffix;

$arkSuffix = preg_replace('/^ark:[0-9]+\//', '', $arkSuffix);
$arkSuffix = preg_replace('/^[A-Z]+\//', '', $arkSuffix);

// Length validation
if (strlen($arkSuffix) < 4) {
    showErrorPage(
        400,
        'Invalid ARK Format',
        'Formato de ARK Inválido',
        'The provided identifier is too short.',
        'O identificador fornecido é muito curto.',
        'You tried: ' . htmlspecialchars($originalInput) . '. Minimum length is 4 characters.',
        'Você tentou: ' . htmlspecialchars($originalInput) . '. O tamanho mínimo é 4 caracteres.'
    );
}

if (strlen($arkSuffix) > 50) {
    showErrorPage(
        400,
        'Invalid ARK Format',
        'Formato de ARK Inválido',
        'The provided identifier is too long.',
        'O identificador fornecido é muito longo.',
        'You tried: ' . htmlspecialchars($originalInput) . '. Maximum length is 50 characters.',
        'Você tentou: ' . htmlspecialchars($originalInput) . '. O tamanho máximo é 50 caracteres.'
    );
}

// Load database config
$configFile = dirname(__FILE__, 4) . '/config.inc.php';
if (!file_exists($configFile)) {
    showErrorPage(
        500,
        'Configuration Error',
        'Erro de Configuração',
        'Could not locate the system configuration file.',
        'Não foi possível localizar o arquivo de configuração do sistema.',
        'Make sure config.inc.php exists in the OJS root directory.',
        'Verifique se o arquivo config.inc.php existe na raiz da instalação do OJS.'
    );
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

// Try with and without hyphen
$tentativas = [$arkSuffix];
if (strpos($arkSuffix, '-') === false && strlen($arkSuffix) >= 8) {
    $tentativas[] = substr($arkSuffix, 0, -4) . '-' . substr($arkSuffix, -4);
} elseif (strpos($arkSuffix, '-') !== false) {
    $tentativas[] = str_replace('-', '', $arkSuffix);
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
        showErrorPage(
            404,
            'ARK Not Found',
            'ARK Não Encontrado',
            'The identifier was not found in our database.',
            'O identificador não foi encontrado em nossa base de dados.',
            'You tried: ' . htmlspecialchars($originalInput),
            'Você tentou: ' . htmlspecialchars($originalInput)
        );
    }
    
    // Get journal path
    $stmt2 = $pdo->prepare("SELECT path FROM journals WHERE journal_id = ?");
    $stmt2->execute([$resultado['context_id']]);
    $journal = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    if (!$journal) {
        showErrorPage(
            500,
            'Journal Error',
            'Erro no Periódico',
            'Could not identify the journal associated with this ARK.',
            'Não foi possível identificar o periódico associado a este ARK.',
            'Contact the system administrator.',
            'Entre em contato com o administrador do sistema.'
        );
    }
    
    // Get friendly URL if exists
    $stmt3 = $pdo->prepare("
        SELECT setting_value FROM publication_settings 
        WHERE publication_id = ? AND setting_name = 'urlPath'
        LIMIT 1
    ");
    $stmt3->execute([$resultado['publication_id']]);
    $urlPath = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
    
    // Redirect without locale - let OJS handle language detection
    if ($urlPath && !empty($urlPath['setting_value'])) {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/article/view/{$urlPath['setting_value']}";
    } else {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/article/view/{$resultado['publication_id']}";
    }
    
    header('HTTP/1.1 302 Found');
    header('Location: ' . $redirectUrl);
    exit;
    
} catch (PDOException $e) {
    error_log("ARK Resolver PDO Error: " . $e->getMessage());
    showErrorPage(
        500,
        'Database Error',
        'Erro no Banco de Dados',
        'Could not connect to the database.',
        'Não foi possível conectar ao banco de dados.',
        'Contact the system administrator.',
        'Entre em contato com o administrador do sistema.'
    );
} catch (Exception $e) {
    error_log("ARK Resolver Error: " . $e->getMessage());
    showErrorPage(
        500,
        'Internal Error',
        'Erro Interno',
        'An internal error occurred.',
        'Ocorreu um erro interno.',
        'Contact the system administrator.',
        'Entre em contato com o administrador do sistema.'
    );
}
