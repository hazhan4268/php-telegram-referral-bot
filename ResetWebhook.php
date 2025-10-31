<?php
require_once __DIR__ . '/Common.php';
class ToolsResetWebhook {
    public static function handle() {
        header('Content-Type: application/json; charset=utf-8');
        $cfg = ToolsCommon::parseConfig();
        if (empty($cfg) || empty($cfg['BOT_TOKEN'])) { echo json_encode(['success'=>false,'error'=>'config.php missing or BOT_TOKEN not set']); return; }
        if (!ToolsCommon::authRequired($cfg)) return;
        $token = $cfg['BOT_TOKEN'];
        $site  = !empty($cfg['SITE_URL']) ? rtrim($cfg['SITE_URL'], '/') : '';
        $url   = $site ? ($site . '/webhook.php') : '';
        $secret = $cfg['WEBHOOK_SECRET'] ?? '';
        $target = $_GET['target'] ?? '';
        if ($target === 'debug' && $site) { $url = $site . '/tools.php?a=webhook_debug'; }
        if (!empty($_GET['path']) && $site) { $base = basename($_GET['path']); $url = $site . '/' . $base; }
        if (!empty($_GET['url'])) { $url = $_GET['url']; }
        $noSecret = isset($_GET['no_secret']) && in_array(strtolower((string)$_GET['no_secret']), ['1','true','yes'], true);
        try {
            $steps = [];
            list($code1, $res1) = ToolsCommon::tg($token, 'deleteWebhook', ['drop_pending_updates' => true]);
            $steps[] = ['deleteWebhook', $code1, $res1];
            if ($code1 !== 200 || !$res1 || !$res1['ok']) { echo json_encode(['success'=>false,'step'=>'deleteWebhook','response'=>$res1]); return; }
            $payload = ['url' => $url];
            if (!$noSecret && !empty($secret)) { $payload['secret_token'] = $secret; }
            list($code2, $res2) = ToolsCommon::tg($token, 'setWebhook', $payload);
            $steps[] = ['setWebhook', $code2, $res2];
            if ($code2 !== 200 || !$res2 || !$res2['ok']) { echo json_encode(['success'=>false,'step'=>'setWebhook','response'=>$res2]); return; }
            echo json_encode(['success'=>true,'url'=>$url,'used_secret'=>(!$noSecret && !empty($secret)), 'steps'=>$steps]);
        } catch (Throwable $e) { echo json_encode(['success'=>false,'error'=>$e->getMessage()]); }
    }
}
