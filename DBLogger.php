<?php
class ErrorDBLogger {
    public static function write($title, $message, $context = '') {
        if (!class_exists('Database')) return false;
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO admin_errors (type, message, context, created_at) VALUES (?, ?, ?, ?)",
                [$title, $message, is_string($context) ? $context : json_encode($context), time()]
            );
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
