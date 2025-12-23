<?php
/**
 * CLI validator for Reportico project queries.
 *
 * Usage:
 *   php tools/validate_reportico_queries.php
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from CLI.\n");
    exit(2);
}

$root = realpath(__DIR__ . '/..');
if (!$root) {
    fwrite(STDERR, "Cannot resolve project root.\n");
    exit(2);
}

// Load Dolibarr conf file to get DB credentials without bootstrapping the full framework.
$confFile = $root . '/../conf/conf.php';
if (!is_file($confFile)) {
    fwrite(STDERR, "Dolibarr conf.php not found at: {$confFile}\n");
    exit(2);
}

require $confFile;

// Build PDO connection (MySQL/MariaDB expected for these projects).
$dbHost = (string)($dolibarr_main_db_host ?? '');
$dbPort = (string)($dolibarr_main_db_port ?? '');
$dbName = (string)($dolibarr_main_db_name ?? '');
$dbUser = (string)($dolibarr_main_db_user ?? '');
$dbPass = (string)($dolibarr_main_db_pass ?? '');
$dbCharset = (string)($dolibarr_main_db_character_set ?? 'utf8mb4');
$dbPrefix = (string)($dolibarr_main_db_prefix ?? 'llx_');

if ($dbHost === '' || $dbName === '' || $dbUser === '') {
    fwrite(STDERR, "Dolibarr DB credentials not found in conf.php (host/name/user).\n");
    exit(2);
}

[$host, $port] = parseHostPort($dbHost);
if ($port === null && $dbPort !== '' && ctype_digit($dbPort)) {
    $port = (int)$dbPort;
}

$dsn = "mysql:host={$host};dbname={$dbName};charset={$dbCharset}";
if ($port !== null) {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$dbCharset}";
}

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "Cannot connect to DB using PDO: {$e->getMessage()}\n");
    exit(2);
}

$projectsDir = $root . '/includes/reportico/projects';
if (!is_dir($projectsDir)) {
    fwrite(STDERR, "Projects directory not found at: {$projectsDir}\n");
    exit(2);
}

$xmlFiles = findXmlFiles($projectsDir);

$totalSql = 0;
$errors = [];

foreach ($xmlFiles as $file) {
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = true;
    $loaded = @$doc->load($file);
    if (!$loaded) {
        $errors[] = [
            'file' => $file,
            'sql' => null,
            'error' => 'XML parse error',
        ];
        continue;
    }

    $xpath = new DOMXPath($doc);
    $nodes = $xpath->query('//SQLRaw');
    if (!$nodes) {
        continue;
    }

    foreach ($nodes as $node) {
        $raw = trim((string)$node->textContent);
        if ($raw === '') {
            continue;
        }
        $totalSql++;

        $sql = normalizeReporticoSql($raw);
        if ($sql === null) {
            continue;
        }

        $sql = applyPrefixReplacement($sql, $dbPrefix);

        // Validate via EXPLAIN where possible.
        try {
            $pdo->query('EXPLAIN ' . $sql);
        } catch (Throwable $e) {
            // Fallback: try wrapping without ORDER BY for syntax/table checking.
            $fallback = stripTopLevelOrderBy($sql);
            $fallback = stripTopLevelLimit($fallback);
            $wrapped = 'SELECT 1 FROM (' . $fallback . ') _t LIMIT 0';
            try {
                $pdo->query($wrapped);
            } catch (Throwable $e2) {
                $errors[] = [
                    'file' => $file,
                    'sql' => $sql,
                    'error' => $e2->getMessage(),
                ];
            }
        }
    }
}

$relRoot = rtrim($root, '/') . '/';

echo "Reportico SQL validation\n";
echo "Root: {$root}\n";
echo "Projects: {$projectsDir}\n";
echo "DB: {$dbName}@{$dbHost}\n";
echo "XML files: " . count($xmlFiles) . "\n";
echo "SQLRaw found: {$totalSql}\n";
echo "Errors: " . count($errors) . "\n\n";

if ($errors) {
    foreach ($errors as $idx => $err) {
        $fileRel = str_starts_with($err['file'], $relRoot) ? substr($err['file'], strlen($relRoot)) : $err['file'];
        echo str_repeat('-', 80) . "\n";
        echo "#" . ($idx + 1) . " File: {$fileRel}\n";
        echo "Error: {$err['error']}\n";
        if (!empty($err['sql'])) {
            echo "SQL: " . preg_replace('/\s+/', ' ', $err['sql']) . "\n";
        }
    }
    echo "\n";
    exit(1);
}

echo "OK - no SQL errors detected by PDO/EXPLAIN\n";
exit(0);

function findXmlFiles(string $dir): array
{
    $out = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }
        $path = $fileInfo->getPathname();
        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'xml') {
            continue;
        }
        $out[] = $path;
    }
    sort($out);
    return $out;
}

function parseHostPort(string $host): array
{
    $host = trim($host);
    if ($host === '') {
        return ['127.0.0.1', null];
    }
    // Handle host:port (avoid breaking IPv6 which contains multiple colons).
    if (substr_count($host, ':') === 1) {
        [$h, $p] = explode(':', $host, 2);
        $p = trim($p);
        if ($p !== '' && ctype_digit($p)) {
            return [trim($h), (int)$p];
        }
    }
    return [$host, null];
}

function normalizeReporticoSql(string $sql): ?string
{
    $sql = str_replace(["\r\n", "\r"], "\n", $sql);
    $sql = trim($sql);

    // Remove Reportico optional blocks [ ... ]
    $sql = preg_replace('/\[[^\]]*\]/s', ' ', $sql);

    // Replace Reportico parameters {param}
    $sql = preg_replace_callback('/\{([^}]+)\}/', function ($m) {
        $name = strtolower(trim((string)$m[1]));
        if ($name === '') {
            return '1';
        }
        if (str_contains($name, 'fecha') || str_contains($name, 'date')) {
            return "'2000-01-01'";
        }
        // For IN (...) lists, a numeric literal is safe.
        return '1';
    }, $sql);

    // Collapse whitespace
    $sql = preg_replace('/\s+/', ' ', $sql);
    $sql = trim($sql);

    // Only validate SELECT statements.
    if (!preg_match('/^select\b/i', $sql)) {
        return null;
    }
    return $sql;
}

function applyPrefixReplacement(string $sql, string $prefix): string
{
    $prefix = $prefix !== '' ? $prefix : 'llx_';
    return str_replace('llx_', $prefix, $sql);
}

function stripTopLevelOrderBy(string $sql): string
{
    // Best-effort: remove last ORDER BY ... if present.
    return preg_replace('/\s+order\s+by\s+.+$/i', '', $sql) ?? $sql;
}

function stripTopLevelLimit(string $sql): string
{
    return preg_replace('/\s+limit\s+\d+(\s*,\s*\d+)?\s*$/i', '', $sql) ?? $sql;
}
