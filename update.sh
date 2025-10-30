#!/bin/bash

###############################################################################
# اسکریپت آپدیت دستی از GitHub
# این اسکریپت را هر وقت بخواهید کدها را از GitHub آپدیت کنید اجرا کنید
###############################################################################

# رنگ‌ها
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}=================================================${NC}"
echo -e "${GREEN}  🔄 آپدیت از GitHub  ${NC}"
echo -e "${GREEN}=================================================${NC}"
echo ""

# مسیر پروژه
PROJECT_PATH=$(pwd)
BRANCH="main"

# تابع برای نمایش خطا
error_exit() {
    echo -e "${RED}❌ خطا: $1${NC}" 1>&2
    exit 1
}

# بررسی Git
if ! command -v git &> /dev/null; then
    error_exit "Git نصب نیست!"
fi

# بررسی .git directory
if [ ! -d ".git" ]; then
    error_exit "این پوشه یک Git repository نیست!"
fi

echo -e "${YELLOW}📍 مسیر: ${PROJECT_PATH}${NC}"
echo -e "${YELLOW}🌿 Branch: ${BRANCH}${NC}"
echo ""

# نمایش وضعیت فعلی
echo -e "${YELLOW}📊 وضعیت فعلی Git:${NC}"
git status --short
echo ""

# سوال تایید
read -p "آیا مایل به آپدیت هستید؟ (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "آپدیت لغو شد."
    exit 0
fi
echo ""

# Backup از فایل config قبل از آپدیت
echo -e "${YELLOW}💾 ایجاد Backup از config.php...${NC}"
if [ -f "config.php" ]; then
    cp config.php config.php.backup
    echo -e "${GREEN}✓ Backup ذخیره شد${NC}"
fi
echo ""

# Stash تغییرات محلی
echo -e "${YELLOW}📦 ذخیره تغییرات محلی...${NC}"
git stash push -m "Auto backup before update $(date +%Y%m%d_%H%M%S)"
echo -e "${GREEN}✓ تغییرات محلی ذخیره شد${NC}"
echo ""

# Fetch از GitHub
echo -e "${YELLOW}📥 دریافت آپدیت‌ها از GitHub...${NC}"
git fetch origin ${BRANCH} || error_exit "خطا در fetch"
echo -e "${GREEN}✓ آپدیت‌ها دریافت شد${NC}"
echo ""

# نمایش تغییرات
echo -e "${YELLOW}📋 تغییرات جدید:${NC}"
git log HEAD..origin/${BRANCH} --oneline --decorate --color
echo ""

# Pull تغییرات
echo -e "${YELLOW}⬇️  اعمال تغییرات...${NC}"
git pull origin ${BRANCH} || error_exit "خطا در pull"
echo -e "${GREEN}✓ تغییرات اعمال شد${NC}"
echo ""

# بازگرداندن config.php
echo -e "${YELLOW}🔧 بازگرداندن config.php...${NC}"
if [ -f "config.php.backup" ]; then
    mv config.php.backup config.php
    echo -e "${GREEN}✓ config.php بازگردانده شد${NC}"
fi
echo ""

# افزایش شماره بیلد محلی پس از آپدیت موفق
echo -e "${YELLOW}🏷️  افزایش شماره Build...${NC}"
BUILD_FILE="BUILD"
if [ -f "$BUILD_FILE" ]; then
    BUILD=$(cat "$BUILD_FILE" 2>/dev/null | tr -d '\r')
    if [[ "$BUILD" =~ ^[0-9]+$ ]]; then
        BUILD=$((BUILD+1))
    else
        BUILD=1
    fi
else
    BUILD=1
fi
echo -n "$BUILD" > "$BUILD_FILE"
echo -e "${GREEN}✓ Build = ${BUILD}${NC}"
echo ""

# تنظیم مجوزات
echo -e "${YELLOW}🔒 تنظیم مجوزات...${NC}"
chmod -R 755 .
chmod 644 config.php 2>/dev/null || true
chmod 644 .htaccess 2>/dev/null || true
echo -e "${GREEN}✓ مجوزات تنظیم شد${NC}"
echo ""

# نمایش نسخه فعلی
CURRENT_COMMIT=$(git rev-parse --short HEAD)
COMMIT_MSG=$(git log -1 --pretty=%B)
COMMIT_DATE=$(git log -1 --pretty=%cd --date=format:'%Y-%m-%d %H:%M')

echo -e "${GREEN}=================================================${NC}"
echo -e "${GREEN}✅ آپدیت با موفقیت انجام شد!${NC}"
echo -e "${GREEN}=================================================${NC}"
echo ""
echo -e "${YELLOW}📌 نسخه فعلی:${NC}"
echo -e "  Commit: ${CURRENT_COMMIT}"
echo -e "  پیام: ${COMMIT_MSG}"
echo -e "  تاریخ: ${COMMIT_DATE}"
echo ""

# نمایش فایل‌های تغییر یافته
echo -e "${YELLOW}📝 فایل‌های تغییر یافته:${NC}"
git diff --name-status HEAD@{1} HEAD
echo ""

echo -e "${GREEN}🎉 پروژه شما به‌روز است!${NC}"
echo ""
