<?php
class ErrorSnapshot {
    public static function write($title, $message, $context, array $meta = [], $keep = 100) {
        $dir = __DIR__ . '/../../logs/errors';
        if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
        $slug = preg_replace('~[^a-z0-9\-_.]+~i', '_', (string)$title);
        $file = sprintf('%s/%s_%s_%s.log', $dir, date('Ymd_His'), $slug ?: 'error', substr(uniqid('', true), -6));
        $snap = [
            'time' => date('Y-m-d H:i:s'),
            'title' => (string)$title,
            'message' => (string)$message,
            'context' => is_string($context) ? $context : json_encode($context, JSON_UNESCAPED_UNICODE),
            'meta' => $meta,
        ];
        @file_put_contents($file, json_encode($snap, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        // prune
        $files = @glob($dir . '/*.log');
        if (is_array($files) && count($files) > $keep) {
            usort($files, function($a, $b){ return filemtime($a) <=> filemtime($b); });
            $toDelete = array_slice($files, 0, max(0, count($files) - $keep));
            foreach ($toDelete as $f) { @unlink($f); }
        }
    }
}
