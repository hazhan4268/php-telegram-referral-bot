<?php
/**
 * Debug Webhook Handler
 * برای تست و دیباگ webhook
 */

// بارگذاری تنظیمات
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    error_log("Config file not found");
    die(json_encode(['error' => 'Bot not configured. Run install.php first.']));
}

require_once __DIR__ . '/config.php';

// لاگ تمام درخواست‌ها
$logFile = __DIR__ . '/webhook_debug.log';
$timestamp = date('Y-m-d H:i:s');
$input = file_get_contents('php://input');
$headers = getallheaders();

$logEntry = "=== {$timestamp} ===\n";
$logEntry .= "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
$logEntry .= "Headers: " . json_encode($headers, JSON_PRETTY_PRINT) . "\n";
$logEntry .= "Input: " . $input . "\n";
$logEntry .= "Input Length: " . strlen($input) . "\n\n";

file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

// بررسی webhook secret
if (defined('WEBHOOK_SECRET') && !empty(WEBHOOK_SECRET)) {
    $receivedSecret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($receivedSecret !== WEBHOOK_SECRET) {
        $error = "Invalid secret. Expected: " . WEBHOOK_SECRET . ", Received: " . $receivedSecret;
        file_put_contents($logFile, "ERROR: {$error}\n\n", FILE_APPEND | LOCK_EX);
        http_response_code(403);
        die('Invalid secret');
    }
}

// بررسی JSON
$update = json_decode($input, true);
if (!$update) {
    $error = "Invalid JSON: " . json_last_error_msg();
    file_put_contents($logFile, "ERROR: {$error}\n\n", FILE_APPEND | LOCK_EX);
    http_response_code(400);
    die('Invalid JSON');
}

// لاگ پردازش شده
file_put_contents($logFile, "SUCCESS: Webhook processed successfully\n\n", FILE_APPEND | LOCK_EX);

// بارگذاری کلاس‌های اصلی
if (file_exists(__DIR__ . '/includes/Database.php') && file_exists(__DIR__ . '/includes/BotHelper.php')) {
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/BotHelper.php';
    
    try {
        // تست اتصال دیتابیس
        $db = Database::getInstance();
        
        // پردازش ساده برای تست
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';
            
            // پاسخ ساده برای تست
            if ($text === '/test') {
                $response = "✅ ربات فعال است!\n\n";
                $response .= "⏰ زمان: " . date('Y-m-d H:i:s') . "\n";
                $response .= "🆔 Chat ID: {$chatId}\n";
                $response .= "🔧 Debug mode: ON";
                
                BotHelper::sendMessage($chatId, $response);
            }
        }
        
        echo 'ok';
    } catch (Exception $e) {
        $error = "Exception: " . $e->getMessage();
        file_put_contents($logFile, "ERROR: {$error}\n\n", FILE_APPEND | LOCK_EX);
        echo 'error';
    }
} else {
    file_put_contents($logFile, "ERROR: Missing includes files\n\n", FILE_APPEND | LOCK_EX);
    echo 'missing_includes';
}
?>