<?php
class ToolsCommon {
    protected static $projectRoot = null;

    public static function projectRoot() {
        if (self::$projectRoot !== null) {
            return self::$projectRoot;
        }
        $candidates = [];
        $dir = __DIR__;
        for ($i = 0; $i < 5; $i++) {
            if (!in_array($dir, $candidates, true)) {
                $candidates[] = $dir;
            }
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }
        foreach ($candidates as $candidate) {
            if (is_file($candidate . '/config.php')) {
                self::$projectRoot = $candidate;
                return self::$projectRoot;
            }
        }
        // Fall back to the original directory when config.php is missing.
        self::$projectRoot = __DIR__;
        return self::$projectRoot;
    }
    public static function parseConfig() {
        $file = self::projectRoot() . '/config.php';
        $out = [];
        if (!is_file($file)) return $out;
        $code = @file_get_contents($file);
        if ($code === false) return $out;
        $keys = ['BOT_TOKEN','WEBHOOK_SECRET','SITE_URL','DEBUG_MODE'];
        foreach ($keys as $key) {
            if (preg_match("/define\(\s*'" . preg_quote($key, '/') . "'\s*,\s*(?:'([^']*)'|\"([^\"]*)\"|([a-zA-Z0-9_:\\/.+-]+))\s*\)\s*;/", $code, $m)) {
                $val = $m[1] !== '' ? $m[1] : ($m[2] !== '' ? $m[2] : $m[3]);
                $out[$key] = $val;
            }
        }
        return $out;
    }
    public static function authRequired($cfg) {
        if (function_exists('session_status') && session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $authorized = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
        $tokenParam = $_GET['token'] ?? '';
        $cfgSecret  = $cfg['WEBHOOK_SECRET'] ?? '';
        if (!$authorized && $cfgSecret !== '' && $tokenParam !== '' && hash_equals($cfgSecret, $tokenParam)) {
            $authorized = true;
        }
        if (!$authorized) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden: admin login or valid token required']);
            return false;
        }
        return true;
    }
    public static function tg($token, $method, $data = []) {
        $api = 'https://api.telegram.org/bot' . $token . '/' . $method;
        $payload = !empty($data) ? json_encode($data) : null;
        if (function_exists('curl_init')) {
            $ch = curl_init($api);
            if ($payload !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } else {
            $opts = ['http' => ['method' => $payload !== null ? 'POST' : 'GET', 'timeout' => 10]];
            if ($payload !== null) {
                $opts['http']['header'] = "Content-Type: application/json\r\n";
                $opts['http']['content'] = $payload;
            }
            $context = stream_context_create($opts);
            $res = @file_get_contents($api, false, $context);
            $code = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $h) {
                    if (preg_match('/^HTTP\/\\d+\.\\d+\\s+(\\d+)/', $h, $m)) { $code = (int)$m[1]; break; }
                }
            }
        }
        return [$code, json_decode($res, true)];
    }
}
