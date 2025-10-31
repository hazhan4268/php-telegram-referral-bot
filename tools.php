<?php
// Thin router delegating to modular handlers.
if (function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Load error handler early (optional) from either legacy or flat layouts.
$errorHandlerCandidates = [
    __DIR__ . '/includes/ErrorHandler.php',
    __DIR__ . '/ErrorHandler.php',
];
foreach ($errorHandlerCandidates as $candidate) {
    if (file_exists($candidate)) {
        require_once $candidate;
        break;
    }
}

$action = $_GET['a'] ?? '';

// Resolve a tool script path in both the legacy includes/tools/ layout and the
// flat project-root layout found in newer deployments.
function tools_resolve_script($relative)
{
    $candidates = [
        __DIR__ . '/' . ltrim($relative, '/'),
        __DIR__ . '/includes/tools/' . ltrim($relative, '/'),
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    return null;
}

// Map actions to handler classes and files
$routes = [
    'ping' => ['file' => 'Ping.php', 'class' => 'ToolsPing', 'method' => 'handle'],
    'get_webhook_info' => ['file' => 'WebhookInfo.php', 'class' => 'ToolsWebhookInfo', 'method' => 'handle'],
    'reset_webhook' => ['file' => 'ResetWebhook.php', 'class' => 'ToolsResetWebhook', 'method' => 'handle'],
    'health' => ['file' => 'Health.php', 'class' => 'ToolsHealth', 'method' => 'handle'],
    'webhook_debug' => ['file' => 'WebhookDebug.php', 'class' => 'ToolsWebhookDebug', 'method' => 'handle'],
];

if (isset($routes[$action])) {
    $route = $routes[$action];
    $handlerFile = tools_resolve_script($route['file']);
    if ($handlerFile !== null) {
        require_once $handlerFile;
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
