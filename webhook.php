<?php
/**
 * Webhook Handler
 * دریافت و پردازش پیام‌های تلگرام
 */

// بارگذاری تنظیمات
if (!file_exists(__DIR__ . '/config.php')) {
    http_response_code(500);
    die(json_encode(['error' => 'Bot not configured. Run install.php first.']));
}

require_once __DIR__ . '/config.php';
// Global error/exception handler (send errors to admin)
if (file_exists(__DIR__ . '/includes/ErrorHandler.php')) {
    require_once __DIR__ . '/includes/ErrorHandler.php';
}

// Initialize database and helpers with error handling
try {
    require_once __DIR__ . '/includes/Database.php';
    require_once __DIR__ . '/includes/BotHelper.php';
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Webhook initialization error: " . $e->getMessage());
    if (function_exists('error_notify_admin')) {
        error_notify_admin('webhook_boot', $e->getMessage());
    }
    http_response_code(500);
    die(json_encode(['error' => 'Bot initialization failed']));
}

// بررسی webhook secret
if (defined('WEBHOOK_SECRET') && !empty(WEBHOOK_SECRET)) {
    $secret = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if ($secret !== WEBHOOK_SECRET) {
        http_response_code(403);
        die('Invalid secret');
    }
}

// دریافت داده‌های ورودی
$input = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update) {
    http_response_code(400);
    die('Invalid JSON');
}

// لاگ برای دیباگ (فقط در حالت توسعه)
if (DEBUG_MODE) {
    file_put_contents(__DIR__ . '/debug.log', date('Y-m-d H:i:s') . " - " . $input . "\n", FILE_APPEND);
}

try {
    handleUpdate($update);
    echo 'ok';
} catch (Exception $e) {
    error_log("Webhook error: " . $e->getMessage());
    if (function_exists('error_notify_admin')) {
        error_notify_admin('webhook', $e->getMessage(), substr($input, 0, 500));
    } else {
        BotHelper::logError('webhook', $e->getMessage(), substr($input, 0, 500));
    }
    // Always 200 to prevent Telegram from retry storms; we already logged/notified
    http_response_code(200);
    echo 'ok';
}

/**
 * پردازش آپدیت تلگرام
 */
function handleUpdate($update) {
    // پیام معمولی
    if (isset($update['message'])) {
        handleMessage($update['message']);
    }
    // callback query
    elseif (isset($update['callback_query'])) {
        handleCallbackQuery($update['callback_query']);
    }
}

/**
 * پردازش پیام
 */
function handleMessage($message) {
    $chatId = $message['chat']['id'];
    $from = $message['from'];
    $userId = $from['id'];
    $firstName = $from['first_name'] ?? 'کاربر';
    $username = $from['username'] ?? null;
    $text = $message['text'] ?? '';
    
    // بررسی بن
    if (BotHelper::isBanned($userId)) {
        return;
    }
    
    // بررسی maintenance
    if (BotHelper::getSetting('maintenance', '0') == '1' && $userId != ADMIN_ID) {
        BotHelper::sendMessage($chatId, '🔧 ربات در حال تعمیر و نگهداری است. لطفاً بعداً تلاش کنید.');
        return;
    }
    
    // ثبت کاربر
    BotHelper::ensureUser($userId, $firstName, $username);
    
    // حالت انتظار پیام تماس با ادمین
    $db = Database::getInstance();
    $contactState = $db->fetchOne("SELECT * FROM contact_state WHERE user_id = ?", [$userId]);
    
    if ($contactState && $contactState['awaiting'] == 1) {
        // ارسال پیام به ادمین
        $userLink = $username ? "@{$username}" : $firstName;
        $adminText = "📩 <b>پیام جدید از کاربر</b>\n\n";
        $adminText .= "<b>از:</b> {$userLink} (<code>{$userId}</code>)\n\n";
        $adminText .= "<b>پیام:</b>\n" . BotHelper::escapeHtml($text);
        
        BotHelper::sendMessage(ADMIN_ID, $adminText);
        
        // تأیید برای کاربر
        BotHelper::sendMessage($chatId, '✅ پیام شما به ادمین ارسال شد. منتظر پاسخ باشید.');
        
        // خروج از حالت انتظار
        $db->execute("UPDATE contact_state SET awaiting = 0 WHERE user_id = ?", [$userId]);
        
        return;
    }
    
    // پردازش دستورات ادمین
    if ($userId == ADMIN_ID && strpos($text, '/') === 0) {
        handleAdminCommand($chatId, $text);
        return;
    }
    
    // پردازش دستورات
    if (strpos($text, '/start') === 0) {
        cmdStart($chatId, $userId, $text);
    }
    elseif ($text === '/menu' || $text === 'منو' || $text === '🏠 منوی اصلی') {
        cmdMenu($chatId);
    }
    elseif ($text === '/invite' || $text === 'دعوت' || $text === '👥 دعوت دوستان') {
        cmdInvite($chatId, $userId);
    }
    elseif ($text === '/score' || $text === 'امتیاز' || $text === '📊 امتیاز من') {
        cmdScore($chatId, $userId);
    }
    elseif ($text === '/top' || $text === 'برترین‌ها' || $text === '🏆 برترین‌ها') {
        cmdTop($chatId);
    }
    elseif ($text === '/spin' || $text === 'گردونه' || $text === '🎡 گردونه شانس') {
        cmdSpin($chatId, $userId);
    }
    elseif ($text === '/claim' || $text === 'درخواست جایزه' || $text === '🎁 درخواست جایزه') {
        cmdClaim($chatId, $userId);
    }
    elseif ($text === '/contact' || $text === 'تماس با ادمین' || $text === '📞 تماس با ادمین') {
        cmdContact($chatId, $userId);
    }
    elseif ($text === '/cancel' || $text === 'لغو') {
        $db->execute("UPDATE contact_state SET awaiting = 0 WHERE user_id = ?", [$userId]);
        BotHelper::sendMessage($chatId, 'لغو شد.');
        cmdMenu($chatId);
    }
    elseif ($text === '/help' || $text === 'راهنما' || $text === '❓ راهنما') {
        cmdHelp($chatId);
    }
    elseif ($text === '/about' || $text === 'درباره' || $text === 'ℹ️ درباره ربات') {
        cmdAbout($chatId);
    }
    else {
        // پاسخ پیش‌فرض
        BotHelper::sendMessage($chatId, 'دستور نامعتبر. از /menu استفاده کنید.');
    }
}

/**
 * پردازش callback query
 */
function handleCallbackQuery($callbackQuery) {
    $callbackId = $callbackQuery['id'];
    $from = $callbackQuery['from'];
    $userId = $from['id'];
    $chatId = $callbackQuery['message']['chat']['id'];
    $messageId = $callbackQuery['message']['message_id'];
    $data = $callbackQuery['data'];
    
    // بررسی بن
    if (BotHelper::isBanned($userId)) {
        BotHelper::answerCallback($callbackId, 'شما مسدود شده‌اید.', true);
        return;
    }
    
    // پردازش callback ها
    if ($data === 'verify') {
        handleVerify($callbackId, $chatId, $messageId, $userId);
    }
    elseif ($data === 'spin') {
        handleSpin($callbackId, $chatId, $messageId, $userId);
    }
    elseif ($data === 'menu') {
        BotHelper::answerCallback($callbackId);
        cmdMenu($chatId);
    }
    else {
        BotHelper::answerCallback($callbackId, 'دستور نامعتبر');
    }
}

/**
 * دستور /start
 */
function cmdStart($chatId, $userId, $text) {
    $db = Database::getInstance();
    
    // بررسی referrer
    $parts = explode(' ', $text, 2);
    if (count($parts) > 1) {
        $referrerId = (int)$parts[1];
        
        if ($referrerId > 0 && $referrerId != $userId) {
            // بررسی ارجاع تکراری
            $existing = $db->fetchOne("SELECT 1 FROM referrals WHERE referred_id = ?", [$userId]);
            
            if (!$existing) {
                // ثبت ارجاع
                $db->execute(
                    "INSERT INTO referrals (referred_id, referrer_id, created_at, credited) VALUES (?, ?, ?, 0)",
                    [$userId, $referrerId, time()]
                );
            }
        }
    }
    
    // بررسی جوین
    $check = BotHelper::checkAllChannels($userId);
    
    if (!$check['joined']) {
        $channels = $check['missing'];
        $text = "👋 سلام! به ربات ارجاع پرمیوم خوش آمدید.\n\n";
        $text .= "❌ برای استفاده از ربات، ابتدا باید در کانال(های) زیر عضو شوید:\n\n";
        
        foreach ($channels as $i => $channel) {
            $text .= ($i + 1) . ". {$channel}\n";
        }
        
        $text .= "\n✅ بعد از عضویت، دکمه «تأیید عضویت» را بزنید.";
        
        $keyboard = [
            [['text' => '📣 عضویت در کانال', 'url' => 'https://t.me/' . ltrim($channels[0], '@')]],
            [['text' => '✅ تأیید عضویت', 'callback_data' => 'verify']]
        ];
        
        BotHelper::sendMessage($chatId, $text, BotHelper::inlineKeyboard($keyboard));
        return;
    }
    
    // پیام خوشامدگویی
    $banner = BotHelper::renderBanner($userId);
    $threshold = BotHelper::getSetting('reward_threshold', 5);
    
    $welcomeText = "🎉 <b>خوش آمدید!</b>\n\n";
    $welcomeText .= "با استفاده از این ربات می‌توانید با دعوت دوستان خود، امتیاز جمع کنید و جوایز ارزشمند دریافت کنید.\n\n";
    $welcomeText .= "📌 <b>چطور کار می‌کند؟</b>\n";
    $welcomeText .= "• لینک اختصاصی خود را با دوستان به اشتراک بگذارید\n";
    $welcomeText .= "• هر {$threshold} دعوت موفق = ۱ پرمیوم تلگرام 🎁\n";
    $welcomeText .= "• روزانه گردونه شانس بچرخانید و امتیاز رایگان بگیرید\n\n";
    $welcomeText .= "🔗 <b>لینک دعوت شما:</b>\n";
    $welcomeText .= BotHelper::getInviteLink($userId);
    
    $keyboard = [
        [['text' => '👥 دعوت دوستان'], ['text' => '📊 امتیاز من']],
        [['text' => '🎡 گردونه شانس'], ['text' => '🎁 درخواست جایزه']],
        [['text' => '🏆 برترین‌ها'], ['text' => '❓ راهنما']],
        [['text' => 'ℹ️ درباره ربات'], ['text' => '📞 تماس با ادمین']]
    ];
    
    BotHelper::sendMessage($chatId, $welcomeText, BotHelper::replyKeyboard($keyboard), 'HTML');
}

/**
 * دستور /menu
 */
function cmdMenu($chatId) {
    $keyboard = [
        [['text' => '👥 دعوت دوستان'], ['text' => '📊 امتیاز من']],
        [['text' => '🎡 گردونه شانس'], ['text' => '🎁 درخواست جایزه']],
        [['text' => '🏆 برترین‌ها'], ['text' => '❓ راهنما']],
        [['text' => 'ℹ️ درباره ربات'], ['text' => '📞 تماس با ادمین']]
    ];
    
    BotHelper::sendMessage($chatId, '🏠 <b>منوی اصلی</b>', BotHelper::replyKeyboard($keyboard));
}

/**
 * دستور /invite
 */
function cmdInvite($chatId, $userId) {
    $link = BotHelper::getInviteLink($userId);
    $db = Database::getInstance();
    
    $approved = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = ? AND credited = 1",
        [$userId]
    );
    $approvedCount = $approved['cnt'] ?? 0;
    
    $text = "🔗 <b>لینک دعوت اختصاصی شما:</b>\n\n";
    $text .= "<code>{$link}</code>\n\n";
    $text .= "👥 تعداد دعوت‌های تأیید شده: <b>{$approvedCount}</b>\n\n";
    $text .= "این لینک را با دوستان خود به اشتراک بگذارید تا امتیاز کسب کنید!";
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * دستور /score
 */
function cmdScore($chatId, $userId) {
    $db = Database::getInstance();
    
    $score = BotHelper::getScore($userId);
    $threshold = (int)BotHelper::getSetting('reward_threshold', 5);
    
    $approved = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = ? AND credited = 1",
        [$userId]
    );
    $approvedCount = $approved['cnt'] ?? 0;
    
    $pending = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = ? AND credited = 0",
        [$userId]
    );
    $pendingCount = $pending['cnt'] ?? 0;
    
    $claims = $db->fetchOne(
        "SELECT COUNT(*) as cnt FROM claims WHERE user_id = ?",
        [$userId]
    );
    $claimsCount = $claims['cnt'] ?? 0;
    
    $progress = ($score % $threshold);
    $bar = str_repeat('▓', $progress) . str_repeat('░', $threshold - $progress);
    
    $text = "📊 <b>آمار شما</b>\n\n";
    $text .= "💰 امتیاز فعلی: <b>{$score}</b>\n";
    $text .= "✅ دعوت‌های تأیید شده: <b>{$approvedCount}</b>\n";
    $text .= "⏳ در انتظار تأیید: <b>{$pendingCount}</b>\n";
    $text .= "🎁 جوایز دریافتی: <b>{$claimsCount}</b>\n\n";
    $text .= "📈 پیشرفت تا جایزه بعدی:\n";
    $text .= "[{$bar}] {$progress}/{$threshold}";
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * دستور /top
 */
function cmdTop($chatId) {
    $db = Database::getInstance();
    
    $top = $db->fetchAll(
        "SELECT u.id, u.first_name, u.username, s.score 
         FROM scores s 
         JOIN users u ON s.user_id = u.id 
         ORDER BY s.score DESC 
         LIMIT 10"
    );
    
    if (empty($top)) {
        BotHelper::sendMessage($chatId, 'هنوز کاربری امتیازی کسب نکرده است.');
        return;
    }
    
    $text = "🏆 <b>برترین کاربران</b>\n\n";
    
    $medals = ['🥇', '🥈', '🥉'];
    
    foreach ($top as $i => $user) {
        $medal = $medals[$i] ?? ($i + 1) . '.';
        $name = BotHelper::escapeHtml($user['first_name']);
        $score = $user['score'];
        
        $text .= "{$medal} {$name} - <b>{$score}</b> امتیاز\n";
    }
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * دستور /spin
 */
function cmdSpin($chatId, $userId) {
    // بررسی جوین
    $check = BotHelper::checkAllChannels($userId);
    if (!$check['joined']) {
        BotHelper::sendMessage($chatId, '❌ برای استفاده از این قسمت، ابتدا باید در کانال(ها) عضو شوید.');
        return;
    }
    
    handleSpin(null, $chatId, null, $userId);
}

/**
 * دستور /claim
 */
function cmdClaim($chatId, $userId) {
    // بررسی جوین
    $check = BotHelper::checkAllChannels($userId);
    if (!$check['joined']) {
        BotHelper::sendMessage($chatId, '❌ برای درخواست جایزه، ابتدا باید در کانال(ها) عضو شوید.');
        return;
    }
    
    $db = Database::getInstance();
    $score = BotHelper::getScore($userId);
    $threshold = (int)BotHelper::getSetting('reward_threshold', 5);
    
    if ($score < $threshold) {
        BotHelper::sendMessage($chatId, "❌ امتیاز شما کافی نیست. شما {$score} امتیاز دارید، حداقل {$threshold} امتیاز نیاز است.");
        return;
    }
    
    // بررسی claim pending
    $pending = $db->fetchOne(
        "SELECT * FROM claims WHERE user_id = ? AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
        [$userId]
    );
    
    if ($pending) {
        BotHelper::sendMessage($chatId, '⏳ شما یک درخواست در حال بررسی دارید. لطفاً منتظر بمانید.');
        return;
    }
    
    // ثبت claim
    $db->execute(
        "INSERT INTO claims (user_id, score_at_claim, status, created_at, updated_at) VALUES (?, ?, 'pending', ?, ?)",
        [$userId, $score, time(), time()]
    );
    
    $claimId = $db->lastInsertId();
    
    // اطلاع به ادمین
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
    $name = BotHelper::escapeHtml($user['first_name']);
    $username = $user['username'] ? "@{$user['username']}" : 'ندارد';
    
    $adminText = "🎁 <b>درخواست جایزه جدید</b>\n\n";
    $adminText .= "<b>کاربر:</b> {$name}\n";
    $adminText .= "<b>یوزرنیم:</b> {$username}\n";
    $adminText .= "<b>شناسه:</b> <code>{$userId}</code>\n";
    $adminText .= "<b>امتیاز:</b> {$score}\n";
    $adminText .= "<b>شماره درخواست:</b> #{$claimId}\n\n";
    $adminText .= "برای پردازش به پنل ادمین مراجعه کنید.";
    
    BotHelper::sendMessage(ADMIN_ID, $adminText);
    
    // پاسخ به کاربر
    BotHelper::sendMessage($chatId, "✅ درخواست شما با موفقیت ثبت شد.\n\n📝 شماره پیگیری: <b>#{$claimId}</b>\n\nادمین به زودی درخواست شما را بررسی خواهد کرد.");
}

/**
 * دستور /contact
 */
function cmdContact($chatId, $userId) {
    $db = Database::getInstance();
    $db->execute(
        "INSERT INTO contact_state (user_id, awaiting, started_at) VALUES (?, 1, ?)
         ON DUPLICATE KEY UPDATE awaiting = 1, started_at = ?",
        [$userId, time(), time()]
    );
    
    $keyboard = [[['text' => 'لغو']]];
    BotHelper::sendMessage($chatId, "📝 پیام خود را برای ادمین بنویسید:\n\n(برای لغو، /cancel را ارسال کنید)", BotHelper::replyKeyboard($keyboard));
}

/**
 * دستور /help
 */
function cmdHelp($chatId) {
    $text = "❓ <b>راهنمای استفاده</b>\n\n";
    $text .= "🔹 <b>دعوت دوستان:</b>\n";
    $text .= "لینک اختصاصی خود را با دوستان به اشتراک بگذارید. هر دوستی که از لینک شما وارد شود و در کانال عضو شود، برای شما امتیاز می‌آورد.\n\n";
    $text .= "🔹 <b>گردونه شانس:</b>\n";
    $text .= "روزانه یک بار می‌توانید گردونه را بچرخانید و 1 تا 5 امتیاز رایگان بگیرید.\n\n";
    $text .= "🔹 <b>درخواست جایزه:</b>\n";
    $text .= "وقتی امتیاز کافی جمع کردید، می‌توانید درخواست دریافت پرمیوم تلگرام بدهید.\n\n";
    $text .= "🔹 <b>برترین‌ها:</b>\n";
    $text .= "مشاهده 10 کاربر برتر بر اساس امتیاز.\n\n";
    $text .= "📞 برای هرگونه سوال با ادمین تماس بگیرید.";
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * دستور /about
 */
function cmdAbout($chatId) {
    $version = APP_VERSION;
    $text = "<b>🤖 درباره ربات</b>\n\n";
    $text .= "<b>📌 نام:</b> ربات ارجاع پرمیوم\n";
    $text .= "<b>🔢 نسخه:</b> {$version}\n";
    $text .= "<b>💻 پلتفرم:</b> PHP\n";
    $text .= "<b>👨‍💼 ادمین:</b> <code>" . ADMIN_ID . "</code>\n\n";
    $text .= "<b>✨ امکانات:</b>\n";
    $text .= "• سیستم دعوت با لینک اختصاصی\n";
    $text .= "• گردونه شانس روزانه\n";
    $text .= "• امتیازدهی خودکار\n";
    $text .= "• درخواست جایزه آنلاین\n";
    $text .= "• پنل مدیریت پیشرفته\n";
    $text .= "• جوین اجباری کانال\n\n";
    $text .= "🔗 برای دعوت دوستان از منو استفاده کنید.";
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * تأیید عضویت
 */
function handleVerify($callbackId, $chatId, $messageId, $userId) {
    $check = BotHelper::checkAllChannels($userId);
    
    if (!$check['joined']) {
        BotHelper::answerCallback($callbackId, '❌ هنوز در همه کانال‌ها عضو نشده‌اید.', true);
        return;
    }
    
    // بروزرسانی وضعیت
    $db = Database::getInstance();
    $db->execute("UPDATE users SET join_status = 1, last_join_check = ? WHERE id = ?", [time(), $userId]);
    
    // بررسی و اعطای امتیاز به referrer
    $referral = $db->fetchOne(
        "SELECT * FROM referrals WHERE referred_id = ? AND credited = 0",
        [$userId]
    );
    
    if ($referral) {
        $referrerId = $referral['referrer_id'];
        
        // اعطای امتیاز
        BotHelper::addScore($referrerId, 1, "دعوت کاربر {$userId}");
        
        // بروزرسانی referral
        $db->execute(
            "UPDATE referrals SET credited = 1, credited_at = ? WHERE referred_id = ?",
            [time(), $userId]
        );
        
        // اطلاع به referrer
        $threshold = (int)BotHelper::getSetting('reward_threshold', 5);
        $newScore = BotHelper::getScore($referrerId);
        
        $notifText = "🎉 <b>دعوت موفق!</b>\n\n";
        $notifText .= "یک نفر از طریق لینک شما وارد شد و عضو کانال شد.\n";
        $notifText .= "✅ +1 امتیاز\n\n";
        $notifText .= "💰 امتیاز فعلی شما: <b>{$newScore}</b>\n";
        
        if ($newScore >= $threshold) {
            $notifText .= "\n🎁 امتیاز شما کافی است! می‌توانید درخواست جایزه بدهید.";
        }
        
        BotHelper::sendMessage($referrerId, $notifText);
    }
    
    BotHelper::answerCallback($callbackId, '✅ عضویت شما تأیید شد!');
    
    // نمایش منو
    cmdStart($chatId, $userId, '/start');
}

/**
 * چرخاندن گردونه
 */
function handleSpin($callbackId, $chatId, $messageId, $userId) {
    $db = Database::getInstance();
    
    // محاسبه روز فعلی (بر اساس تهران)
    $tehranTime = new DateTime('now', new DateTimeZone('Asia/Tehran'));
    $dayIndex = (int)$tehranTime->format('Ymd');
    
    // بررسی آخرین بار چرخش
    $spin = $db->fetchOne("SELECT * FROM spins WHERE user_id = ?", [$userId]);
    
    if ($spin && $spin['last_day'] == $dayIndex) {
        $msg = '⏰ شما امروز گردونه را چرخانده‌اید. فردا دوباره امتحان کنید!';
        
        if ($callbackId) {
            BotHelper::answerCallback($callbackId, $msg, true);
        } else {
            BotHelper::sendMessage($chatId, $msg);
        }
        return;
    }
    
    // امتیاز تصادفی 1-5
    $points = rand(1, 5);
    
    // اعطای امتیاز
    BotHelper::addScore($userId, $points, 'گردونه شانس');
    
    // بروزرسانی spins
    if ($spin) {
        $db->execute(
            "UPDATE spins SET last_day = ?, last_at = ?, total_spins = total_spins + 1, total_points = total_points + ? WHERE user_id = ?",
            [$dayIndex, time(), $points, $userId]
        );
    } else {
        $db->execute(
            "INSERT INTO spins (user_id, last_day, last_at, total_spins, total_points) VALUES (?, ?, ?, 1, ?)",
            [$userId, $dayIndex, time(), $points]
        );
    }
    
    $newScore = BotHelper::getScore($userId);
    
    $emojis = ['🎉', '🎊', '✨', '💫', '⭐', '🌟'];
    $randomEmoji = $emojis[array_rand($emojis)];
    
    $text = "{$randomEmoji} <b>گردونه چرخید!</b>\n\n";
    $text .= "🎁 شما <b>{$points}</b> امتیاز بردید!\n";
    $text .= "💰 امتیاز کل: <b>{$newScore}</b>\n\n";
    $text .= "فردا دوباره برگردید! 🔄";
    
    if ($callbackId) {
        BotHelper::answerCallback($callbackId, "🎉 +{$points} امتیاز!");
    }
    
    BotHelper::sendMessage($chatId, $text);
}

/**
 * دستورات ادمین
 */
function handleAdminCommand($chatId, $text) {
    $parts = explode(' ', $text, 3);
    $cmd = $parts[0];
    
    if ($cmd === '/stats') {
        $db = Database::getInstance();
        
        $totalUsers = $db->fetchOne("SELECT COUNT(*) as cnt FROM users")['cnt'];
        $totalReferrals = $db->fetchOne("SELECT COUNT(*) as cnt FROM referrals WHERE credited = 1")['cnt'];
        $pendingClaims = $db->fetchOne("SELECT COUNT(*) as cnt FROM claims WHERE status = 'pending'")['cnt'];
        $totalScore = $db->fetchOne("SELECT SUM(score) as total FROM scores")['total'] ?? 0;
        
        $text = "📊 <b>آمار سیستم</b>\n\n";
        $text .= "👥 کل کاربران: <b>{$totalUsers}</b>\n";
        $text .= "✅ دعوت‌های موفق: <b>{$totalReferrals}</b>\n";
        $text .= "⏳ درخواست‌های در انتظار: <b>{$pendingClaims}</b>\n";
        $text .= "💰 مجموع امتیازات: <b>{$totalScore}</b>\n\n";
        $text .= "🔗 پنل ادمین: " . SITE_URL . "/admin/";
        
        BotHelper::sendMessage($chatId, $text);
    }
    elseif ($cmd === '/ban' && isset($parts[1])) {
        $targetId = (int)$parts[1];
        $reason = $parts[2] ?? 'بدون دلیل';
        
        $db = Database::getInstance();
        $db->execute(
            "INSERT INTO bans (user_id, reason, banned_at) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE reason = ?, banned_at = ?",
            [$targetId, $reason, time(), $reason, time()]
        );
        
        BotHelper::sendMessage($chatId, "✅ کاربر {$targetId} مسدود شد.");
        BotHelper::sendMessage($targetId, "🚫 شما توسط ادمین مسدود شدید.\n\nدلیل: {$reason}");
    }
    elseif ($cmd === '/unban' && isset($parts[1])) {
        $targetId = (int)$parts[1];
        
        $db = Database::getInstance();
        $db->execute("DELETE FROM bans WHERE user_id = ?", [$targetId]);
        
        BotHelper::sendMessage($chatId, "✅ کاربر {$targetId} آزاد شد.");
        BotHelper::sendMessage($targetId, "✅ مسدودیت شما برداشته شد. می‌توانید دوباره از ربات استفاده کنید.");
    }
    elseif ($cmd === '/give' && isset($parts[1]) && isset($parts[2])) {
        $targetId = (int)$parts[1];
        $points = (int)$parts[2];
        
        if ($points > 0) {
            BotHelper::addScore($targetId, $points, 'اعطا توسط ادمین');
            $newScore = BotHelper::getScore($targetId);
            
            BotHelper::sendMessage($chatId, "✅ {$points} امتیاز به کاربر {$targetId} داده شد. امتیاز جدید: {$newScore}");
            BotHelper::sendMessage($targetId, "🎁 ادمین به شما <b>{$points}</b> امتیاز داد!\n\n💰 امتیاز فعلی: <b>{$newScore}</b>");
        }
    }
    elseif ($cmd === '/take' && isset($parts[1]) && isset($parts[2])) {
        $targetId = (int)$parts[1];
        $points = (int)$parts[2];
        
        if ($points > 0) {
            BotHelper::addScore($targetId, -$points, 'کسر توسط ادمین');
            $newScore = BotHelper::getScore($targetId);
            
            BotHelper::sendMessage($chatId, "✅ {$points} امتیاز از کاربر {$targetId} کسر شد. امتیاز جدید: {$newScore}");
            BotHelper::sendMessage($targetId, "⚠️ ادمین <b>{$points}</b> امتیاز از شما کسر کرد.\n\n💰 امتیاز فعلی: <b>{$newScore}</b>");
        }
    }
    elseif ($cmd === '/r' && isset($parts[1])) {
        // پاسخ به کاربر
        $targetId = (int)$parts[1];
        $message = $parts[2] ?? '';
        
        if (!empty($message)) {
            BotHelper::sendMessage($targetId, "💬 <b>پاسخ ادمین:</b>\n\n{$message}");
            BotHelper::sendMessage($chatId, "✅ پاسخ ارسال شد.");
        }
    }
    else {
        $help = "🔧 <b>دستورات ادمین:</b>\n\n";
        $help .= "/stats - آمار سیستم\n";
        $help .= "/ban [id] [دلیل] - مسدود کردن کاربر\n";
        $help .= "/unban [id] - آزاد کردن کاربر\n";
        $help .= "/give [id] [امتیاز] - اعطای امتیاز\n";
        $help .= "/take [id] [امتیاز] - کسر امتیاز\n";
        $help .= "/r [id] [پیام] - پاسخ به کاربر\n\n";
        $help .= "🌐 پنل کامل: " . SITE_URL . "/admin/";
        
        BotHelper::sendMessage($chatId, $help);
    }
}
