<?php
header('Content-Type: application/json; charset=utf-8');

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
    ],
    'db' => [
        'ok' => false,
        'error' => null,
    ],
    'write' => [
        'deploy_log' => null,
    ],
];

if ($result['config']['exists']) {
    require_once __DIR__ . '/config.php';
}

// Test DB
try {
    require_once __DIR__ . '/includes/Database.php';
    $db = Database::getInstance();
    $db->fetchOne('SELECT 1');
    $result['db']['ok'] = true;
} catch (Throwable $e) {
    $result['db']['ok'] = false;
    $msg = $e->getMessage();
    // hide credentials if accidentally included
    $msg = str_replace([DB_USER ?? '', DB_PASS ?? ''], ['***', '***'], $msg);
    $result['db']['error'] = $msg;
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
if (!$result['db']['ok']) $hints[] = 'اتصال به دیتابیس برقرار نشد؛ اطلاعات دیتابیس را بررسی کنید';
$result['hints'] = $hints;

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
