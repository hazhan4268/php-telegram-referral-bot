<?php
/**
 * Cron Job Handler
 * وظایف دوره‌ای سیستم
 * 
 * در cPanel این cron job را اضافه کنید:
 * */5 * * * * php /home/username/public_html/cron.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/BotHelper.php';

$db = Database::getInstance();

// بررسی فعال بودن cron
$enabled = BotHelper::getSetting('enable_cron', '1');
if ($enabled != '1') {
    exit('Cron disabled');
}

// به‌روزرسانی زمان آخرین اجرا
BotHelper::setSetting('cron_last_run', time());

echo "Starting cron job at " . date('Y-m-d H:i:s') . "\n";

/**
 * 1. پاکسازی throttle قدیمی (بیش از 1 ساعت)
 */
try {
    $deleted = $db->execute("DELETE FROM throttle WHERE at < ?", [time() - 3600]);
    echo "Cleaned {$deleted} old throttle records\n";
} catch (Exception $e) {
    echo "Error cleaning throttle: " . $e->getMessage() . "\n";
}

/**
 * 2. پاکسازی member_cache قدیمی (بیش از 1 ساعت)
 */
try {
    $deleted = $db->execute("DELETE FROM member_cache WHERE cached_at < ?", [time() - 3600]);
    echo "Cleaned {$deleted} old member_cache records\n";
} catch (Exception $e) {
    echo "Error cleaning member_cache: " . $e->getMessage() . "\n";
}

/**
 * 3. پاکسازی لاگ‌های قدیمی
 */
try {
    $retention = (int)BotHelper::getSetting('logs_retention_days', 7);
    $maxRows = (int)BotHelper::getSetting('logs_max_rows', 50000);
    
    // حذف لاگ‌های قدیمی‌تر از retention days
    $deleted = $db->execute(
        "DELETE FROM logs WHERE time < ?",
        [time() - ($retention * 86400)]
    );
    echo "Cleaned {$deleted} old log records\n";
    
    // حذف اضافی‌ها اگر بیش از maxRows باشد
    $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM logs")['cnt'];
    if ($count > $maxRows) {
        $db->execute(
            "DELETE FROM logs WHERE id IN (
                SELECT id FROM (
                    SELECT id FROM logs ORDER BY time ASC LIMIT ?
                ) tmp
            )",
            [$count - $maxRows]
        );
        echo "Cleaned excess log records (kept last {$maxRows})\n";
    }
} catch (Exception $e) {
    echo "Error cleaning logs: " . $e->getMessage() . "\n";
}

/**
 * 4. پاکسازی admin_errors قدیمی
 */
try {
    $retention = (int)BotHelper::getSetting('admin_errors_retention_days', 7);
    $maxRows = (int)BotHelper::getSetting('admin_errors_max_rows', 20000);
    
    $deleted = $db->execute(
        "DELETE FROM admin_errors WHERE created_at < ?",
        [time() - ($retention * 86400)]
    );
    echo "Cleaned {$deleted} old error records\n";
    
    $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM admin_errors")['cnt'];
    if ($count > $maxRows) {
        $db->execute(
            "DELETE FROM admin_errors WHERE id IN (
                SELECT id FROM (
                    SELECT id FROM admin_errors ORDER BY created_at ASC LIMIT ?
                ) tmp
            )",
            [$count - $maxRows]
        );
        echo "Cleaned excess error records (kept last {$maxRows})\n";
    }
} catch (Exception $e) {
    echo "Error cleaning admin_errors: " . $e->getMessage() . "\n";
}

/**
 * 5. پاکسازی session های قدیمی (بیش از 30 روز)
 */
try {
    $deleted = $db->execute(
        "DELETE FROM admin_sessions WHERE created_at < ?",
        [time() - (30 * 86400)]
    );
    echo "Cleaned {$deleted} old admin sessions\n";
} catch (Exception $e) {
    echo "Error cleaning sessions: " . $e->getMessage() . "\n";
}

/**
 * 6. بررسی سلامت کانال‌ها (هر 1 ساعت)
 */
try {
    $lastCheck = (int)BotHelper::getSetting('channels_health_check', 0);
    if (time() - $lastCheck > 3600) {
        $channels = $db->fetchAll("SELECT * FROM channels WHERE active = 1");
        
        foreach ($channels as $channel) {
            // تست دسترسی به کانال
            $result = BotHelper::telegramRequest('getChat', [
                'chat_id' => $channel['username']
            ]);
            
            if (!$result || !$result['ok']) {
                // کانال در دسترس نیست
                BotHelper::logError(
                    'channel_check',
                    "کانال {$channel['username']} در دسترس نیست",
                    json_encode($result)
                );
            }
        }
        
        BotHelper::setSetting('channels_health_check', time());
        echo "Checked health of " . count($channels) . " channels\n";
    }
} catch (Exception $e) {
    echo "Error checking channels health: " . $e->getMessage() . "\n";
}

/**
 * 7. آمار روزانه (هر 24 ساعت)
 */
try {
    $lastStats = (int)BotHelper::getSetting('daily_stats_sent', 0);
    $today = strtotime('today');
    
    if ($lastStats < $today) {
        // محاسبه آمار دیروز
        $yesterday = $today - 86400;
        
        $newUsers = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM users WHERE joined_at >= ? AND joined_at < ?",
            [$yesterday, $today]
        )['cnt'] ?? 0;
        
        $newReferrals = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM referrals WHERE created_at >= ? AND created_at < ?",
            [$yesterday, $today]
        )['cnt'] ?? 0;
        
        $newClaims = $db->fetchOne(
            "SELECT COUNT(*) as cnt FROM claims WHERE created_at >= ? AND created_at < ?",
            [$yesterday, $today]
        )['cnt'] ?? 0;
        
        // ارسال گزارش به ادمین
        $report = "📊 <b>گزارش روزانه</b>\n\n";
        $report .= "📅 تاریخ: " . date('Y/m/d', $yesterday) . "\n\n";
        $report .= "👥 کاربران جدید: {$newUsers}\n";
        $report .= "✅ دعوت‌های جدید: {$newReferrals}\n";
        $report .= "🎁 درخواست‌های جدید: {$newClaims}\n";
        
        BotHelper::sendMessage(ADMIN_ID, $report);
        
        BotHelper::setSetting('daily_stats_sent', time());
        echo "Sent daily stats report\n";
    }
} catch (Exception $e) {
    echo "Error sending daily stats: " . $e->getMessage() . "\n";
}

/**
 * 8. پاکسازی contact_state قدیمی (بیش از 1 ساعت)
 */
try {
    $deleted = $db->execute(
        "UPDATE contact_state SET awaiting = 0 WHERE awaiting = 1 AND started_at < ?",
        [time() - 3600]
    );
    echo "Reset {$deleted} old contact states\n";
} catch (Exception $e) {
    echo "Error resetting contact states: " . $e->getMessage() . "\n";
}

echo "Cron job completed at " . date('Y-m-d H:i:s') . "\n";
echo "---\n";
