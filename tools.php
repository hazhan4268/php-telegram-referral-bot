<?php
// Thin router delegating to modular handlers under includes/tools/
if (function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Load error handler early (optional)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

$action = $_GET['a'] ?? '';

// Map actions to handler classes and files
$routes = [
    'ping' => ['file' => __DIR__ . '/Ping.php', 'class' => 'ToolsPing', 'method' => 'handle'],
    'get_webhook_info' => ['file' => __DIR__ . '/WebhookInfo.php', 'class' => 'ToolsWebhookInfo', 'method' => 'handle'],
    'reset_webhook' => ['file' => __DIR__ . '/ResetWebhook.php', 'class' => 'ToolsResetWebhook', 'method' => 'handle'],
    'health' => ['file' => __DIR__ . '/Health.php', 'class' => 'ToolsHealth', 'method' => 'handle'],
    'webhook_debug' => ['file' => __DIR__ . '/WebhookDebug.php', 'class' => 'ToolsWebhookDebug', 'method' => 'handle'],
];

if (isset($routes[$action])) {
    $route = $routes[$action];
    if (file_exists($route['file'])) {
        require_once $route['file'];
        if (class_exists($route['class']) && method_exists($route['class'], $route['method'])) {
            call_user_func([$route['class'], $route['method']]);
            return;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Handler not found or invalid']);
    return;
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'ok' => true,
    'actions' => array_keys($routes),
    'usage' => [
        '/tools.php?a=ping',
        '/tools.php?a=health',
        '/tools.php?a=get_webhook_info&token=WEBHOOK_SECRET',
        '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET',
        '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET&no_secret=1',
        '/tools.php?a=reset_webhook&token=WEBHOOK_SECRET&target=debug'
    ]
]);
