#!/bin/bash

###############################################################################
# اسکریپت راه‌اندازی اولیه Git در cPanel
# این اسکریپت را یکبار در سرور اجرا کنید
###############################################################################

echo "🚀 راه‌اندازی Git Deployment در cPanel..."
echo ""

# رنگ‌ها برای خروجی
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# مسیر پروژه (تغییر دهید)
PROJECT_PATH="/home/username/public_html"
REPO_URL="https://github.com/hazhan4268/php-telegram-referral-bot.git"
BRANCH="main"

echo -e "${YELLOW}مرحله 1: بررسی نصب Git${NC}"
if ! command -v git &> /dev/null; then
    echo -e "${RED}✗ Git نصب نیست!${NC}"
    echo "لطفاً از cPanel یا SSH، Git را نصب کنید"
    exit 1
fi
echo -e "${GREEN}✓ Git نصب است${NC}"
echo ""

echo -e "${YELLOW}مرحله 2: تنظیم Git Config${NC}"
git config --global user.name "Server Deploy"
git config --global user.email "deploy@yourdomain.com"
echo -e "${GREEN}✓ Git config تنظیم شد${NC}"
echo ""

echo -e "${YELLOW}مرحله 3: Initialize Git Repository${NC}"
cd "$PROJECT_PATH" || exit

# اگر .git وجود نداشت
if [ ! -d ".git" ]; then
    echo "Initialize کردن repository..."
    git init
    git remote add origin "$REPO_URL"
    echo -e "${GREEN}✓ Repository initialized${NC}"
else
    echo -e "${GREEN}✓ Repository از قبل موجود است${NC}"
fi
echo ""

echo -e "${YELLOW}مرحله 4: Fetch و Pull اولیه${NC}"
git fetch origin "$BRANCH"
git checkout -b "$BRANCH" "origin/$BRANCH" 2>/dev/null || git checkout "$BRANCH"
git pull origin "$BRANCH"
echo -e "${GREEN}✓ کدها دانلود شدند${NC}"
echo ""

echo -e "${YELLOW}مرحله 5: تنظیم مجوزات${NC}"
chmod -R 755 .
chmod 644 config.php 2>/dev/null || echo "config.php not found"
chmod 644 .htaccess 2>/dev/null || echo ".htaccess not found"
chmod 755 deploy.php 2>/dev/null || echo "deploy.php not found"
echo -e "${GREEN}✓ مجوزات تنظیم شدند${NC}"
echo ""

echo -e "${GREEN}✅ راه‌اندازی کامل شد!${NC}"
echo ""
echo -e "${YELLOW}مراحل بعدی:${NC}"
echo "1. فایل deploy.php را در root قرار دهید"
echo "2. SECRET را در deploy.php تنظیم کنید"
echo "3. در GitHub Webhook تنظیم کنید:"
echo "   URL: https://yourdomain.com/deploy.php"
echo "   Content type: application/json"
echo "   Secret: (همان SECRET در deploy.php)"
echo "   Events: Just the push event"
echo ""
