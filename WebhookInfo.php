<?php
require_once __DIR__ . '/Common.php';
class ToolsWebhookInfo {
    public static function handle() {
        header('Content-Type: application/json; charset=utf-8');
        $cfg = ToolsCommon::parseConfig();
        if (empty($cfg) || empty($cfg['BOT_TOKEN'])) { echo json_encode(['ok'=>false,'error'=>'config.php missing or BOT_TOKEN not set']); return; }
        if (!ToolsCommon::authRequired($cfg)) return;
        list($code, $json) = ToolsCommon::tg($cfg['BOT_TOKEN'], 'getWebhookInfo');
        echo json_encode(['http'=>$code,'response'=>$json]);
    }
}
