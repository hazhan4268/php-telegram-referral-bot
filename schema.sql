-- MySQL Schema for Premium Referral Bot
-- Converted from D1/SQLite to MySQL

CREATE TABLE IF NOT EXISTS users (
  id BIGINT PRIMARY KEY,
  first_name VARCHAR(255),
  username VARCHAR(255),
  joined_at INT,
  premium_claimed INT DEFAULT 0,
  note TEXT,
  last_join_check INT DEFAULT 0,
  join_status INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS referrals (
  referred_id BIGINT PRIMARY KEY,
  referrer_id BIGINT NOT NULL,
  created_at INT NOT NULL,
  credited INT NOT NULL DEFAULT 0,
  credited_at INT,
  INDEX idx_referrer (referrer_id),
  INDEX idx_credited (credited),
  INDEX idx_referrer_credited (referrer_id, credited)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scores (
  user_id BIGINT PRIMARY KEY,
  score INT NOT NULL DEFAULT 0,
  updated_at INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS settings (
  `key` VARCHAR(255) PRIMARY KEY,
  value TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS channels (
  id INT PRIMARY KEY AUTO_INCREMENT,
  username VARCHAR(255) UNIQUE NOT NULL,
  invite_link TEXT,
  required INT NOT NULL DEFAULT 1,
  active INT NOT NULL DEFAULT 1,
  created_at INT,
  updated_at INT,
  INDEX idx_active (active),
  INDEX idx_required (required)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS throttle (
  user_id BIGINT NOT NULL,
  action VARCHAR(50) NOT NULL,
  at INT NOT NULL,
  PRIMARY KEY(user_id, action),
  INDEX idx_user_action (user_id, action)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS last_msgs (
  user_id BIGINT PRIMARY KEY,
  last_text TEXT,
  last_type VARCHAR(50),
  last_sent_at INT,
  last_msg_id INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS spins (
  user_id BIGINT PRIMARY KEY,
  last_day INT,
  last_at INT,
  total_spins INT NOT NULL DEFAULT 0,
  total_points INT NOT NULL DEFAULT 0,
  INDEX idx_user_day (user_id, last_day)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS member_cache (
  channel VARCHAR(255) NOT NULL,
  user_id BIGINT NOT NULL,
  status VARCHAR(50),
  cached_at INT NOT NULL,
  PRIMARY KEY(channel, user_id),
  INDEX idx_cached_at (cached_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS claims (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  score_at_claim INT NOT NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'pending',
  created_at INT NOT NULL,
  updated_at INT NOT NULL,
  admin_note TEXT,
  responded_at INT,
  points_deducted INT NOT NULL DEFAULT 0,
  INDEX idx_status (status),
  INDEX idx_user_status (user_id, status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bans (
  user_id BIGINT PRIMARY KEY,
  reason TEXT,
  banned_at INT NOT NULL,
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS score_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  delta INT NOT NULL,
  reason TEXT,
  by_admin VARCHAR(255),
  created_at INT NOT NULL,
  INDEX idx_user (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS post_msgs (
  user_id BIGINT NOT NULL,
  slot VARCHAR(50) NOT NULL,
  msg_id INT,
  last_sent_at INT,
  PRIMARY KEY(user_id, slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_state (
  user_id BIGINT PRIMARY KEY,
  awaiting INT NOT NULL DEFAULT 0,
  started_at INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_sessions (
  session_id VARCHAR(255) PRIMARY KEY,
  admin_id VARCHAR(255) NOT NULL,
  csrf_token VARCHAR(255) NOT NULL,
  created_at INT NOT NULL,
  INDEX idx_admin (admin_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  channel_username VARCHAR(255),
  link TEXT,
  image_url TEXT,
  description TEXT,
  priority INT DEFAULT 0,
  active INT DEFAULT 1,
  created_at INT NOT NULL,
  updated_at INT,
  INDEX idx_active_priority (active, priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsor_views (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sponsor_id INT NOT NULL,
  user_id BIGINT NOT NULL,
  viewed_at INT NOT NULL,
  INDEX idx_sponsor (sponsor_id, viewed_at),
  INDEX idx_user (user_id, viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sponsor_clicks (
  id INT PRIMARY KEY AUTO_INCREMENT,
  sponsor_id INT NOT NULL,
  user_id BIGINT NOT NULL,
  clicked_at INT NOT NULL,
  INDEX idx_sponsor (sponsor_id, clicked_at),
  INDEX idx_user (user_id, clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_errors (
  id INT PRIMARY KEY AUTO_INCREMENT,
  type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  context TEXT,
  created_at INT NOT NULL,
  INDEX idx_created (created_at),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  time INT NOT NULL,
  type VARCHAR(50) NOT NULL,
  message TEXT NOT NULL,
  meta TEXT,
  INDEX idx_time (time),
  INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_logs (
  id INT PRIMARY KEY AUTO_INCREMENT,
  action VARCHAR(255) NOT NULL,
  actor VARCHAR(255),
  meta TEXT,
  created_at INT NOT NULL,
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- تنظیمات پیش‌فرض
INSERT INTO settings (`key`, value) VALUES
  ('reward_threshold', '5'),
  ('banner_text', 'هر {thr} دعوت موفق = ۱ جایزه تلگرام پرمیوم 🎁\nبرای شروع از همین حالا از لینک اختصاصی‌ات استفاده کن: {link}'),
  ('announce_last_version', ''),
  ('announce_msg_id', ''),
  ('announce_last_updated', '0'),
  ('maintenance', '0'),
  ('claim_cooldown_days', '0'),
  ('throttle_window_sec', '3'),
  ('broadcast_max_per_run', '40'),
  ('enable_cron', '1'),
  ('cron_auto_disable_channels', '0'),
  ('cron_last_run', '0'),
  ('logs_retention_days', '7'),
  ('logs_max_rows', '50000'),
  ('admin_errors_retention_days', '7'),
  ('admin_errors_max_rows', '20000'),
  ('bot_username', '')
ON DUPLICATE KEY UPDATE `key`=`key`;
