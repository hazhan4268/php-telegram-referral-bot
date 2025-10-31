<?php
/**
 * Global Error/Exception Handler with Telegram notify
 * ارسال خودکار خطاها به پیوی ادمین (ADMIN_ID)
 * وابسته به BOT_TOKEN و ADMIN_ID در config.php (در صورت نبود، فقط لاگ فایل)
 */

// Prevent redeclare
if (!function_exists('error_notify_admin')) {

    function error_notify_admin($title, $message, $context = '') {
        // Load modules (no fatal if not found)
        @require_once __DIR__ . '/errors/Snapshot.php';
        @require_once __DIR__ . '/errors/Throttle.php';
        @require_once __DIR__ . '/errors/Notifier.php';
        @require_once __DIR__ . '/errors/FileLogger.php';
        @require_once __DIR__ . '/errors/DBLogger.php';

        // Config knobs
        if (!defined('ERROR_SNAPSHOT_KEEP')) define('ERROR_SNAPSHOT_KEEP', 100);
        if (!defined('ERROR_NOTIFY_THROTTLE')) define('ERROR_NOTIFY_THROTTLE', 600); // seconds

        // Common metadata
        $meta = [
            'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'CLI',
            'uri'  => isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
            'time' => date('Y-m-d H:i:s'),
            'version' => defined('APP_VERSION') ? APP_VERSION : 'unknown',
        ];

        // Snapshot (best effort)
        if (class_exists('ErrorSnapshot')) {
            ErrorSnapshot::write($title, $message, $context, $meta, (int)ERROR_SNAPSHOT_KEEP);
        }

        // Try Telegram (with throttle)
        if (defined('BOT_TOKEN') && defined('ADMIN_ID') && BOT_TOKEN && ADMIN_ID) {
            $allow = true;
            if (class_exists('ErrorThrottle')) {
                $allow = ErrorThrottle::allow('tg_' . $title, (int)ERROR_NOTIFY_THROTTLE);
            }
            if ($allow && class_exists('ErrorNotifier')) {
                ErrorNotifier::sendTelegram($title, $message, $context, $meta);
            }
        }

        // Also try to persist in DB if available
        if (class_exists('ErrorDBLogger')) {
            ErrorDBLogger::write($title, $message, $context);
        }

        // Always write to a local log file
        if (class_exists('ErrorFileLogger')) {
            ErrorFileLogger::write($title, $message);
        }
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
