<?php
class ErrorThrottle {
    public static function allow($key, $ttlSeconds = 600) {
        $ttl = max(0, (int)$ttlSeconds);
        if ($ttl === 0) return true;
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
        $flag = $dir . DIRECTORY_SEPARATOR . 'err_throttle_' . preg_replace('~[^a-z0-9\-_.]+~i', '_', $key) . '.txt';
        $now = time();
        $last = @is_file($flag) ? (int)@file_get_contents($flag) : 0;
        if ($now - $last > $ttl) {
            @file_put_contents($flag, (string)$now, LOCK_EX);
            return true;
        }
        return false;
    }
}
