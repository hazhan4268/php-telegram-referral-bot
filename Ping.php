<?php
require_once __DIR__ . '/Common.php';
class ToolsPing { public static function handle() { header('Content-Type: text/plain; charset=utf-8'); echo "pong\n"; }}
