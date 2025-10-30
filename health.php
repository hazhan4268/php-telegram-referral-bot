<?php
header('Content-Type: application/json; charset=utf-8');

// Helper: safely parse constants from config.php without executing it
function parse_config_constants($file)
{
    $out = [];
    if (!is_file($file)) return $out;
    $code = @file_get_contents($file);
    if ($code === false) return $out;
    $map = [
        'DB_HOST','DB_NAME','DB_USER','DB_PASS','DB_CHARSET',
        'SITE_URL','WEBHOOK_URL','WEBHOOK_SECRET','DEBUG_MODE','TIMEZONE'
    ];
    foreach ($map as $key) {
        // match: define('KEY', 'value'); or define('KEY', true/false);
        if (preg_match("/define\(\s*'" . preg_quote($key, '/') . "'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_]+))\s*\)\s*;/", $code, $m)) {
            $val = null;
            if (isset($m[1]) && $m[1] !== '') $val = $m[1];
            elseif (isset($m[2]) && $m[2] !== '') $val = $m[2];
            else $val = $m[3];
            // normalize booleans
            if (is_string($val)) {
                $lv = strtolower($val);
                if ($lv === 'true') $val = true;
                elseif ($lv === 'false') $val = false;
            }
            $out[$key] = $val;
        }
    }
    return $out;
}

$result = [
    'time' => date('Y-m-d H:i:s'),
    'php' => [
        'version' => PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '8.0.0', '>='),
    ],
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'curl' => extension_loaded('curl'),
        'json' => extension_loaded('json'),
    ],
    'config' => [
        'exists' => file_exists(__DIR__ . '/config.php'),
        'parsed' => false,
    ],
    'db' => [
        'ok' => false,
        'error' => null,
    ],
    'write' => [
        'deploy_log' => null,
    ],
];

// Try to parse config without executing
$cfg = [];
if ($result['config']['exists']) {
    $cfg = parse_config_constants(__DIR__ . '/config.php');
    if (!empty($cfg)) $result['config']['parsed'] = true;
}

// Test DB using parsed config (avoid executing config.php to bypass parse errors)
if (!empty($cfg['DB_HOST']) && !empty($cfg['DB_NAME']) && isset($cfg['DB_USER'])) {
    $host = $cfg['DB_HOST'];
    $dbn  = $cfg['DB_NAME'];
    $usr  = $cfg['DB_USER'];
    $pwd  = $cfg['DB_PASS'] ?? '';
    $chs  = $cfg['DB_CHARSET'] ?? 'utf8mb4';
    if (!extension_loaded('pdo_mysql')) {
        $result['db']['ok'] = false;
        $result['db']['error'] = 'pdo_mysql extension is not loaded';
    } else {
        try {
            $dsn = "mysql:host={$host};dbname={$dbn};charset={$chs}";
            $pdo = new PDO($dsn, $usr, $pwd, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->query('SELECT 1');
            $result['db']['ok'] = true;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            // mask secrets
            $secrets = array_filter([$usr, $pwd]);
            if (!empty($secrets)) $msg = str_replace($secrets, '***', $msg);
            $result['db']['ok'] = false;
            $result['db']['error'] = $msg;
        }
    }
} else {
    $result['db']['ok'] = false;
    $result['db']['error'] = 'config.php not parsed or DB constants missing';
}

// Test write to deploy.log
$logFile = __DIR__ . '/deploy.log';
$test = @file_put_contents($logFile, '[' . date('H:i:s') . "] health check\n", FILE_APPEND);
$result['write']['deploy_log'] = $test !== false;

// Hints
$hints = [];
if (!$result['php']['ok']) $hints[] = 'PHP 8.0+ مورد نیاز است';
if (!$result['extensions']['pdo_mysql']) $hints[] = 'افزونه pdo_mysql باید فعال باشد';
if (!$result['config']['exists']) $hints[] = 'فایل config.php ایجاد نشده است (install.php را اجرا کنید)';
if ($result['config']['exists'] && !$result['config']['parsed']) $hints[] = 'config.php قابل خواندن نیست (خطای نحوی؟ دسترسی فایل؟)';
if (!$result['db']['ok']) $hints[] = 'اتصال به دیتابیس برقرار نشد؛ اطلاعات دیتابیس را بررسی کنید';
$result['hints'] = $hints;

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
