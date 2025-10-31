<?php
require_once __DIR__ . '/Common.php';
class ToolsWebhookDebug {
    public static function handle() {
        // Always 200; log details
        $logFile = __DIR__ . '/../../webhook_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $input = file_get_contents('php://input');
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $logEntry = "=== {$timestamp} ===\n";
        $logEntry .= "Method: " . ($_SERVER['REQUEST_METHOD'] ?? '') . "\n";
        $logEntry .= "Headers: " . json_encode($headers) . "\n";
        $logEntry .= "Input: " . $input . "\n";
        $logEntry .= "Input Length: " . strlen($input) . "\n\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        $cfg = ToolsCommon::parseConfig();
        if (!empty($cfg['WEBHOOK_SECRET'])) {
            $received = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
            if ($received !== $cfg['WEBHOOK_SECRET']) {
                @file_put_contents($logFile, "ERROR: invalid secret\n\n", FILE_APPEND | LOCK_EX);
                echo 'ok'; return;
            }
        }
        $update = json_decode($input, true);
        if (!$update) { @file_put_contents($logFile, "ERROR: invalid json\n\n", FILE_APPEND | LOCK_EX); echo 'ok'; return; }
        if (file_exists(__DIR__ . '/../Database.php') && file_exists(__DIR__ . '/../BotHelper.php')) {
            require_once __DIR__ . '/../Database.php';
            require_once __DIR__ . '/../BotHelper.php';
            try {
                $db = Database::getInstance();
                if (isset($update['message'])) {
                    $chatId = $update['message']['chat']['id'];
                    $text = $update['message']['text'] ?? '';
                    if ($text === '/test') { BotHelper::sendMessage($chatId, "✅ Debug ok: " . date('Y-m-d H:i:s')); }
                }
            } catch (Throwable $e) { @file_put_contents($logFile, "ERROR: exception: " . $e->getMessage() . "\n\n", FILE_APPEND | LOCK_EX); }
        } else { @file_put_contents($logFile, "ERROR: missing includes\n\n", FILE_APPEND | LOCK_EX); }
        echo 'ok';
    }
}
