<?php
class ErrorFileLogger {
    public static function write($title, $message, $file = null) {
        $logFile = $file ?: (__DIR__ . '/../../deploy.log');
        $ts = date('Y-m-d H:i:s');
        @file_put_contents($logFile, "[{$ts}] [ERROR] {$title} | {$message}\n", FILE_APPEND);
    }
}
