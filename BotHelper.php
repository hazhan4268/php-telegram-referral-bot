<?php
/**
 * Helper Functions
 * توابع کمکی برای کار با ربات
 */

class BotHelper {
    private static $db;
    private static $cache = [];
    
    public static function init() {
        self::$db = Database::getInstance();
    }
    
    /**
     * ارسال درخواست به API تلگرام
     */
    public static function telegramRequest($method, $data = []) {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("Telegram API Error ($method): " . $response);
            return false;
        }
        
        return json_decode($response, true);
    }
    
    /**
     * ارسال پیام
     */
    public static function sendMessage($chatId, $text, $keyboard = null, $parseMode = 'HTML') {
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }
        
        return self::telegramRequest('sendMessage', $data);
    }
    
    /**
     * ویرایش پیام
     */
    public static function editMessage($chatId, $messageId, $text, $keyboard = null, $parseMode = 'HTML') {
        $data = [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => $parseMode
        ];
        
        if ($keyboard) {
            $data['reply_markup'] = $keyboard;
        }
        
        return self::telegramRequest('editMessageText', $data);
    }
    
    /**
     * پاسخ به callback query
     */
    public static function answerCallback($callbackId, $text = '', $showAlert = false) {
        return self::telegramRequest('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text' => $text,
            'show_alert' => $showAlert
        ]);
    }
    
    /**
     * بررسی عضویت در کانال
     */
    public static function checkMembership($channelUsername, $userId) {
        $cacheKey = "member_{$channelUsername}_{$userId}";
        
        // بررسی کش
        if (isset(self::$cache[$cacheKey])) {
            $cached = self::$cache[$cacheKey];
            if (time() - $cached['time'] < 5) { // 5 ثانیه کش
                return $cached['status'];
            }
        }
        
        // درخواست به تلگرام
        $result = self::telegramRequest('getChatMember', [
            'chat_id' => $channelUsername,
            'user_id' => $userId
        ]);
        
        $status = false;
        if ($result && isset($result['result']['status'])) {
            $memberStatus = $result['result']['status'];
            $status = in_array($memberStatus, ['member', 'administrator', 'creator']);
        }
        
        // ذخیره در کش
        self::$cache[$cacheKey] = [
            'status' => $status,
            'time' => time()
        ];
        
        return $status;
    }
    
    /**
     * بررسی عضویت در تمام کانال‌های فعال
     */
    public static function checkAllChannels($userId) {
        $channels = self::$db->fetchAll(
            "SELECT username FROM channels WHERE active = 1 AND required = 1"
        );
        
        if (empty($channels)) {
            // اگر از ENV استفاده می‌شود
            if (defined('CHANNEL_USERNAME') && !empty(CHANNEL_USERNAME)) {
                $channels = [['username' => CHANNEL_USERNAME]];
            } else {
                return ['joined' => true, 'missing' => []];
            }
        }
        
        $missing = [];
        foreach ($channels as $channel) {
            if (!self::checkMembership($channel['username'], $userId)) {
                $missing[] = $channel['username'];
            }
        }
        
        return [
            'joined' => empty($missing),
            'missing' => $missing
        ];
    }
    
    /**
     * ساخت کیبورد inline
     */
    public static function inlineKeyboard($buttons) {
        return json_encode(['inline_keyboard' => $buttons]);
    }
    
    /**
     * ساخت کیبورد معمولی
     */
    public static function replyKeyboard($buttons, $resize = true) {
        return json_encode([
            'keyboard' => $buttons,
            'resize_keyboard' => $resize
        ]);
    }
    
    /**
     * دریافت یا ساخت کاربر
     */
    public static function ensureUser($userId, $firstName, $username = null) {
        $user = self::$db->fetchOne(
            "SELECT * FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            self::$db->execute(
                "INSERT INTO users (id, first_name, username, joined_at) VALUES (?, ?, ?, ?)",
                [$userId, $firstName, $username, time()]
            );
            
            $user = self::$db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
        } else {
            // به‌روزرسانی نام
            self::$db->execute(
                "UPDATE users SET first_name = ?, username = ? WHERE id = ?",
                [$firstName, $username, $userId]
            );
        }
        
        return $user;
    }
    
    /**
     * دریافت امتیاز کاربر
     */
    public static function getScore($userId) {
        $score = self::$db->fetchOne(
            "SELECT score FROM scores WHERE user_id = ?",
            [$userId]
        );
        
        return $score ? (int)$score['score'] : 0;
    }
    
    /**
     * افزودن امتیاز
     */
    public static function addScore($userId, $points, $reason = '') {
        $current = self::getScore($userId);
        $new = $current + $points;
        
        self::$db->execute(
            "INSERT INTO scores (user_id, score, updated_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE score = ?, updated_at = ?",
            [$userId, $new, time(), $new, time()]
        );
        
        // لاگ
        self::$db->execute(
            "INSERT INTO score_logs (user_id, delta, reason, created_at) VALUES (?, ?, ?, ?)",
            [$userId, $points, $reason, time()]
        );
        
        return $new;
    }
    
    /**
     * دریافت تنظیمات
     */
    public static function getSetting($key, $default = null) {
        $setting = self::$db->fetchOne(
            "SELECT value FROM settings WHERE `key` = ?",
            [$key]
        );
        
        return $setting ? $setting['value'] : $default;
    }
    
    /**
     * ذخیره تنظیمات
     */
    public static function setSetting($key, $value) {
        self::$db->execute(
            "INSERT INTO settings (`key`, value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE value = ?",
            [$key, $value, $value]
        );
    }
    
    /**
     * فرمت تاریخ شمسی
     */
    public static function jalaliDate($timestamp = null) {
        if (!$timestamp) {
            $timestamp = time();
        }
        
        // استفاده از jDateTime یا کتابخانه دیگر
        // برای سادگی، از تاریخ میلادی استفاده می‌کنیم
        return date('Y/m/d H:i', $timestamp);
    }
    
    /**
     * لاگ خطا
     */
    public static function logError($type, $message, $context = '') {
        try {
            self::$db->execute(
                "INSERT INTO admin_errors (type, message, context, created_at) VALUES (?, ?, ?, ?)",
                [$type, $message, $context, time()]
            );
            
            // ارسال به ادمین
            if (defined('ADMIN_ID')) {
                $text = "🚨 <b>خطای سیستم</b>\n\n";
                $text .= "<b>نوع:</b> {$type}\n";
                $text .= "<b>پیام:</b> {$message}\n";
                if ($context) {
                    $text .= "<b>جزئیات:</b> " . substr($context, 0, 100);
                }
                
                self::sendMessage(ADMIN_ID, $text);
            }
        } catch (Exception $e) {
            error_log("Failed to log error: " . $e->getMessage());
        }
    }
    
    /**
     * بررسی بن
     */
    public static function isBanned($userId) {
        $ban = self::$db->fetchOne(
            "SELECT 1 FROM bans WHERE user_id = ?",
            [$userId]
        );
        
        return (bool)$ban;
    }
    
    /**
     * محدودیت نرخ (throttle)
     */
    public static function shouldThrottle($userId, $action, $windowSec = 3) {
        $throttle = self::$db->fetchOne(
            "SELECT at FROM throttle WHERE user_id = ? AND action = ?",
            [$userId, $action]
        );
        
        if ($throttle && (time() - $throttle['at']) < $windowSec) {
            return true;
        }
        
        self::$db->execute(
            "INSERT INTO throttle (user_id, action, at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE at = ?",
            [$userId, $action, time(), time()]
        );
        
        return false;
    }
    
    /**
     * ساخت لینک دعوت
     */
    public static function getInviteLink($userId) {
        $botUsername = self::getSetting('bot_username', 'YourBot');
        return "https://t.me/{$botUsername}?start={$userId}";
    }
    
    /**
     * رندر بنر
     */
    public static function renderBanner($userId) {
        $threshold = (int)self::getSetting('reward_threshold', 5);
        $banner = self::getSetting('banner_text', 
            'هر {thr} دعوت موفق = ۱ جایزه تلگرام پرمیوم 🎁\nبرای شروع از همین حالا از لینک اختصاصی‌ات استفاده کن: {link}');
        
        $link = self::getInviteLink($userId);
        $linkMd = "[لینک اختصاصی شما]({$link})";
        
        $banner = str_replace('{thr}', $threshold, $banner);
        $banner = str_replace('{link}', $linkMd, $banner);
        
        return $banner;
    }
    
    /**
     * escape HTML
     */
    public static function escapeHtml($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// مقداردهی اولیه
BotHelper::init();
