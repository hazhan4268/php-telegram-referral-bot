<?php
/**
 * Global Error/Exception Handler with Telegram notify
 * ارسال خودکار خطاها به پیوی ادمین (ADMIN_ID)
 * وابسته به BOT_TOKEN و ADMIN_ID در config.php (در صورت نبود، فقط لاگ فایل)
 */

// Prevent redeclare
if (!function_exists('error_notify_admin')) {

    function error_notify_admin($title, $message, $context = '') {
        // Try Telegram
        if (defined('BOT_TOKEN') && defined('ADMIN_ID') && BOT_TOKEN && ADMIN_ID) {
            $maxLen = 3500; // keep below Telegram 4096
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI';
            $uri  = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            $time = date('Y-m-d H:i:s');
            $ver  = defined('APP_VERSION') ? APP_VERSION : 'unknown';

            $lines = [];
            $lines[] = "🚨 خطای سیستم";
            $lines[] = "عنوان: {$title}";
            $lines[] = "زمان: {$time}";
            $lines[] = "نسخه: {$ver}";
            $lines[] = "مسیر: {$host}{$uri}";
            $lines[] = "پیام: {$message}";
            if ($context) { $lines[] = "جزئیات: " . (is_string($context) ? $context : json_encode($context)); }
            $text = implode("\n", $lines);

            if (strlen($text) > $maxLen) {
                $text = substr($text, 0, $maxLen) . "\n…";
            }

            // Send to Telegram without relying on BotHelper
            $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
            $payload = json_encode([
                'chat_id' => ADMIN_ID,
                'text' => $text,
                'disable_web_page_preview' => true
            ]);
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                @curl_exec($ch);
                @curl_close($ch);
            }
        }

        // Also try to persist in DB if available
        if (class_exists('Database')) {
            try {
                $db = Database::getInstance();
                $db->execute(
                    "INSERT INTO admin_errors (type, message, context, created_at) VALUES (?, ?, ?, ?)",
                    [$title, $message, is_string($context) ? $context : json_encode($context), time()]
                );
            } catch (Throwable $e) {
                // ignore
            }
        }

        // Always write to a local log file
        $logFile = __DIR__ . '/../deploy.log';
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$ts}] [ERROR] {$title} | {$message}\n", FILE_APPEND);
    }

    // PHP error handler: notify but don't turn warnings/notices into fatal exceptions
    set_error_handler(function ($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false; // not reporting this level
        }
        $levelsNotifyOnly = [
            E_WARNING,
            E_NOTICE,
            E_USER_WARNING,
            E_USER_NOTICE,
            E_DEPRECATED,
            E_USER_DEPRECATED,
            E_STRICT
        ];
        $payload = $message . ' @ ' . $file . ':' . $line;
        if (in_array($severity, $levelsNotifyOnly, true)) {
            // Notify and continue
            error_notify_admin('php_warning', $payload);
            return false; // allow PHP normal handling/logging
        }
        if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
            // escalate
            throw new ErrorException($message, 0, $severity, $file, $line);
        }
        // default: notify and allow
        error_notify_admin('php_error', $payload);
        return false;
    });

    // Handle uncaught exceptions
    set_exception_handler(function ($e) {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg  = $e->getMessage();
        $code = $e->getCode();
        $title = 'uncaught_exception';
        $context = [
            'file' => $file,
            'line' => $line,
            'code' => $code
        ];
        error_notify_admin($title, $msg, $context);
        // Set 500 but avoid injecting text into HTML pages
        if (php_sapi_name() !== 'cli') {
            http_response_code(500);
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                // Optional minimal debug output
                header('Content-Type: text/plain; charset=utf-8');
                echo 'Exception: ' . $msg;
            }
        }
    });

    // Fatal errors
    register_shutdown_function(function () {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $title = 'fatal_error';
            $msg = $err['message'] . ' @ ' . $err['file'] . ':' . $err['line'];
            error_notify_admin($title, $msg);
        }
    });
}

?>
