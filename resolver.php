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

/**
 * Get the base URL of the OJS installation
 * 
 * @return string Base URL (e.g., https://example.com)
 */
function getSiteBaseUrl() {
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $baseUrl;
}

/**
 * Read database configuration from config.inc.php
 * 
 * @throws Exception If config file is not found or required fields are missing
 * @return array Database configuration (driver, host, username, password, name)
 */
function getDbConfig() {
    $configFile = dirname(__FILE__, 4) . '/config.inc.php';
    
    if (!file_exists($configFile)) {
        throw new Exception('Configuration file not found: ' . $configFile);
    }
    
    $configLines = file($configFile, FILE_IGNORE_NEW_LINES);
    $dbConfig = [];
    $inDbSection = false;
    
    foreach ($configLines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comments
        if (empty($line) || $line[0] === ';' || $line[0] === '#') {
            continue;
        }
        
        // Check for database section
        if (strpos($line, '[database]') === 0) {
            $inDbSection = true;
            continue;
        }
        
        // Check for other sections
        if ($inDbSection && strpos($line, '[') === 0) {
            $inDbSection = false;
            continue;
        }
        
        // Parse configuration values
        if ($inDbSection && strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, " \t\n\r\0\x0B\"'");
            
            $dbConfig[$key] = $value;
        }
    }
    
    // Validate required fields
    $required = ['driver', 'host', 'username', 'password', 'name'];
    foreach ($required as $field) {
        if (empty($dbConfig[$field])) {
            throw new Exception("Missing database configuration: {$field}");
        }
    }
    
    return $dbConfig;
}

/**
 * Extract inflection from query string
 * 
 * Detects '?' (brief metadata) and '??' (full metadata) in the request
 * 
 * @return string|null 'brief' for '?', 'full' for '??', or null if no inflection
 */
function getInflection() {
    // Check for '?' and '??' in query string
    if (empty($_SERVER['QUERY_STRING'])) {
        $requestUri = $_SERVER['REQUEST_URI'];
        if (substr($requestUri, -2) === '??') {
            return 'full';
        } elseif (substr($requestUri, -1) === '?') {
            return 'brief';
        }
        return null;
    }
    
    // For cases like resolver.php?ark=ID? or resolver.php?ark=ID??
    if (isset($_GET['ark'])) {
        $arkValue = $_GET['ark'];
        if (substr($arkValue, -2) === '??') {
            $_GET['ark'] = substr($arkValue, 0, -2);
            return 'full';
        } elseif (substr($arkValue, -1) === '?') {
            $_GET['ark'] = substr($arkValue, 0, -1);
            return 'brief';
        }
    }
    
    if (isset($_GET['id'])) {
        $idValue = $_GET['id'];
        if (substr($idValue, -2) === '??') {
            $_GET['id'] = substr($idValue, 0, -2);
            return 'full';
        } elseif (substr($idValue, -1) === '?') {
            $_GET['id'] = substr($idValue, 0, -1);
            return 'brief';
        }
    }
    
    return null;
}

/**
 * Get metadata for ERC response
 * 
 * Fetches article metadata from OJS database
 * 
 * @param PDO $pdo Database connection
 * @param int $publicationId Publication ID
 * @param int $contextId Journal ID
 * @param string $arkSuffix ARK suffix (without prefix)
 * @param string $baseUrl Site base URL
 * @param string $journalPath Journal path
 * @return array Metadata array with keys: who, what, when, ark_url, base_ark_url, who_journal, issn, support_when
 */
function getMetadataForERC($pdo, $publicationId, $contextId, $arkSuffix, $baseUrl, $journalPath) {
    $metadata = [];
    
    // Get publication basic info
    $stmt = $pdo->prepare("
        SELECT p.*, s.locale as submission_locale
        FROM publications p
        JOIN submissions s ON p.submission_id = s.submission_id
        WHERE p.publication_id = ?
        LIMIT 1
    ");
    $stmt->execute([$publicationId]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get authors with their names from author_settings table
    $stmt = $pdo->prepare("
        SELECT 
            a.author_id,
            a.seq,
            MAX(CASE WHEN as_given.setting_name = 'givenName' THEN as_given.setting_value END) as givenName,
            MAX(CASE WHEN as_family.setting_name = 'familyName' THEN as_family.setting_value END) as familyName
        FROM authors a
        LEFT JOIN author_settings as_given ON a.author_id = as_given.author_id 
            AND as_given.setting_name = 'givenName'
        LEFT JOIN author_settings as_family ON a.author_id = as_family.author_id 
            AND as_family.setting_name = 'familyName'
        WHERE a.publication_id = ?
        GROUP BY a.author_id, a.seq
        ORDER BY a.seq
        LIMIT 3
    ");
    $stmt->execute([$publicationId]);
    $authors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $authorNames = [];
    foreach ($authors as $author) {
        $name = '';
        if (!empty($author['givenName'])) $name .= $author['givenName'] . ' ';
        if (!empty($author['familyName'])) $name .= $author['familyName'];
        if (!empty(trim($name))) $authorNames[] = trim($name);
    }
    
    // Fallback: if no names found, try to get from email
    if (empty($authorNames)) {
        $stmt = $pdo->prepare("
            SELECT email FROM authors WHERE publication_id = ? ORDER BY seq LIMIT 3
        ");
        $stmt->execute([$publicationId]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($emails as $email) {
            $authorNames[] = $email['email'];
        }
    }
    
    $metadata['who'] = !empty($authorNames) ? implode('; ', $authorNames) : 'Unknown author';
    
    // Get title from publication_settings
    $stmt = $pdo->prepare("
        SELECT setting_value FROM publication_settings
        WHERE publication_id = ? AND setting_name = 'title'
        LIMIT 1
    ");
    $stmt->execute([$publicationId]);
    $title = $stmt->fetch(PDO::FETCH_ASSOC);
    $metadata['what'] = $title ? html_entity_decode($title['setting_value'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Untitled';
    
    // Get publication date
    $metadata['when'] = '';
    if (!empty($publication['date_published'])) {
        $metadata['when'] = date('Ymd', strtotime($publication['date_published']));
    } elseif (!empty($publication['last_modified'])) {
        $metadata['when'] = date('Ymd', strtotime($publication['last_modified']));
    } else {
        $metadata['when'] = date('Ymd');
    }
    
    // Get journal title from journal_settings
    $stmt = $pdo->prepare("
        SELECT setting_value FROM journal_settings
        WHERE journal_id = ? AND setting_name = 'name'
        LIMIT 1
    ");
    $stmt->execute([$contextId]);
    $journalName = $stmt->fetch(PDO::FETCH_ASSOC);
    $metadata['who_journal'] = $journalName ? html_entity_decode($journalName['setting_value'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'Journal';
    
    // Get ISSN from journal_settings
    $stmt = $pdo->prepare("
        SELECT setting_value FROM journal_settings
        WHERE journal_id = ? AND (setting_name = 'printIssn' OR setting_name = 'onlineIssn')
        LIMIT 1
    ");
    $stmt->execute([$contextId]);
    $issn = $stmt->fetch(PDO::FETCH_ASSOC);
    $metadata['issn'] = $issn ? $issn['setting_value'] : '';
    
    // Get the full ARK identifier from database
    $stmt = $pdo->prepare("
        SELECT setting_value FROM publication_settings
        WHERE publication_id = ? AND setting_name = 'pub-id::ark'
        LIMIT 1
    ");
    $stmt->execute([$publicationId]);
    $arkFull = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // First 'where' field: Complete ARK URL with trailing slash
    $fullArkId = $arkFull ? $arkFull['setting_value'] : '';
    if (!empty($fullArkId)) {
        if (strpos($fullArkId, 'http') !== 0) {
            $metadata['ark_url'] = 'https://n2t.net/' . ltrim($fullArkId, '/') . '/';
        } else {
            $metadata['ark_url'] = rtrim($fullArkId, '/') . '/';
        }
    } else {
        // Fallback
        $metadata['ark_url'] = $baseUrl . "/plugins/pubIds/ark/resolver.php?ark=" . urlencode($arkSuffix) . '/';
    }
    
    // Second 'where' field (erc-support): Base ARK URL up to NAAN
    $naan = '';
    if (!empty($fullArkId)) {
        if (preg_match('/ark:([0-9]+)\//', $fullArkId, $matches)) {
            $naan = $matches[1];
        }
    }
    $metadata['base_ark_url'] = 'https://n2t.net/ark:' . $naan . '/';
    
    // Get ARK implementation date from journal settings (fixed date)
    $stmt = $pdo->prepare("
        SELECT setting_value FROM journal_settings
        WHERE journal_id = ? AND setting_name = 'arkImplementationDate' AND (locale = '' OR locale IS NULL)
        LIMIT 1
    ");
    $stmt->execute([$contextId]);
    $implDate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Use implementation date if set and valid, otherwise fallback to article publication date
    if ($implDate && !empty($implDate['setting_value']) && preg_match('/^(19|20)\d{6}$/', $implDate['setting_value'])) {
        $metadata['support_when'] = $implDate['setting_value'];
    } else {
        $metadata['support_when'] = $metadata['when'];
    }
    
    return $metadata;
}

/**
 * Output brief ERC metadata (for '?' inflection)
 * 
 * @param array $metadata Metadata array
 * @param string $arkSuffix ARK suffix
 * @param string $fullArkResolverUrl Full resolver URL
 */
function outputBriefERC($metadata, $arkSuffix, $fullArkResolverUrl) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "erc:\n";
    echo "who: " . $metadata['who'] . "\n";
    echo "what: " . $metadata['what'] . "\n";
    echo "when: " . $metadata['when'] . "\n";
    echo "where: " . $metadata['ark_url'] . "\n";
}

/**
 * Output full ERC metadata with support info (for '??' inflection)
 * 
 * @param array $metadata Metadata array
 * @param string $arkSuffix ARK suffix
 * @param string $fullArkResolverUrl Full resolver URL
 */
function outputFullERC($metadata, $arkSuffix, $fullArkResolverUrl) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "erc:\n";
    echo "who: " . $metadata['who'] . "\n";
    echo "what: " . $metadata['what'] . "\n";
    echo "when: " . $metadata['when'] . "\n";
    echo "where: " . $metadata['ark_url'] . "\n";
    echo "erc-support:\n";
    echo "who: " . $metadata['who_journal'] . "\n";
    echo "what: Permanent: Stable Content:\n";
    echo "when: " . $metadata['support_when'] . "\n";
    echo "where: " . $metadata['base_ark_url'] . "\n";
    if (!empty($metadata['issn'])) {
        echo "issn: " . $metadata['issn'] . "\n";
    }
}

/**
 * Display error page in both Portuguese and English
 * 
 * @param int $statusCode HTTP status code
 * @param string $titleEn English title
 * @param string $titlePt Portuguese title
 * @param string $messageEn English message
 * @param string $messagePt Portuguese message
 * @param string $detailsEn English details (optional)
 * @param string $detailsPt Portuguese details (optional)
 */
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
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                max-width: 900px;
                margin: 40px auto;
                padding: 20px;
                line-height: 1.6;
                background-color: #ffffff;
                color: #333333;
            }
            h1 { font-size: 1.8rem; }
            hr { margin: 30px 0; border: none; border-top: 1px solid #cccccc; }
            .language-section {
                margin: 20px 0;
                padding: 20px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
            }
            .language-pt { background-color: #f8f9fa; }
            .language-en { background-color: #ffffff; }
            .language-label {
                font-size: 0.8rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                opacity: 0.7;
                margin-bottom: 10px;
            }
            a { color: #0366d6; text-decoration: none; }
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

// ============ MAIN EXECUTION ============

// Check for '?' and '??' inflections
$inflection = getInflection();

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

// Clean ARK suffix (remove ark: prefix and shoulder)
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

try {
    // Get database configuration
    $dbConfig = getDbConfig();
    
    // Build DSN based on driver
    $dsn = "";
    switch ($dbConfig['driver']) {
        case 'mysqli':
        case 'mysql':
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8";
            break;
        case 'postgres':
        case 'postgresql':
            $dsn = "pgsql:host={$dbConfig['host']};dbname={$dbConfig['name']}";
            break;
        default:
            throw new Exception("Unsupported database driver: " . $dbConfig['driver']);
    }
    
    // Connect to database
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Try with and without hyphen (ARK NAAN format allows both)
    $attempts = [$arkSuffix];
    if (strpos($arkSuffix, '-') === false && strlen($arkSuffix) >= 8) {
        $attempts[] = substr($arkSuffix, 0, -4) . '-' . substr($arkSuffix, -4);
    } elseif (strpos($arkSuffix, '-') !== false) {
        $attempts[] = str_replace('-', '', $arkSuffix);
    }
    
    $result = null;
    
    foreach ($attempts as $suffix) {
        $stmt = $pdo->prepare("
            SELECT ps.publication_id, s.context_id
            FROM publication_settings ps
            JOIN publications p ON ps.publication_id = p.publication_id
            JOIN submissions s ON p.submission_id = s.submission_id
            WHERE ps.setting_name = 'pub-id::ark' 
            AND (ps.setting_value = ? OR ps.setting_value LIKE CONCAT('%', ?))
            LIMIT 1
        ");
        $stmt->execute([$suffix, $suffix]);
        
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result = $row;
            break;
        }
    }
    
    if (!$result) {
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
    $stmt2->execute([$result['context_id']]);
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
    
    $baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $baseUrl = str_replace('/plugins/pubIds/ark', '', $baseUrl);
    $siteBaseUrl = getSiteBaseUrl();
    
    // Check if we need to return ERC metadata
    if ($inflection === 'brief' || $inflection === 'full') {
        $fullArkResolverUrl = $siteBaseUrl . "/plugins/pubIds/ark/resolver.php?ark=" . urlencode($originalInput);
        
        $metadata = getMetadataForERC(
            $pdo, 
            $result['publication_id'], 
            $result['context_id'], 
            $originalInput,
            $siteBaseUrl,
            $journal['path']
        );
        
        if ($inflection === 'brief') {
            outputBriefERC($metadata, $originalInput, $fullArkResolverUrl);
        } else {
            outputFullERC($metadata, $originalInput, $fullArkResolverUrl);
        }
        exit;
    }
    
    // Get friendly URL if exists
    $stmt3 = $pdo->prepare("
        SELECT setting_value FROM publication_settings 
        WHERE publication_id = ? AND setting_name = 'urlPath'
        LIMIT 1
    ");
    $stmt3->execute([$result['publication_id']]);
    $urlPath = $stmt3->fetch(PDO::FETCH_ASSOC);
    
    // Redirect to article page
    if ($urlPath && !empty($urlPath['setting_value'])) {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/article/view/{$urlPath['setting_value']}";
    } else {
        $redirectUrl = $baseUrl . "/index.php/{$journal['path']}/article/view/{$result['publication_id']}";
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
        'Error: ' . $e->getMessage(),
        'Erro: ' . $e->getMessage()
    );
} catch (Exception $e) {
    error_log("ARK Resolver Error: " . $e->getMessage());
    showErrorPage(
        500,
        'Internal Error',
        'Erro Interno',
        'An internal error occurred.',
        'Ocorreu um erro interno.',
        'Error: ' . $e->getMessage(),
        'Erro: ' . $e->getMessage()
    );
}