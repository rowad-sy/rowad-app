#!/usr/bin/env bash
# تحديث أصول الواجهة (public/build) على السيرفر تلقائيًا دون أن يكون مجلد الموقع مستودع git.
# يسحب فرع deploy (يحتوي على public/build المبني جاهزًا عبر GitHub Actions) إلى مجلد منفصل خارج الموقع
# ثم ينسخ public/build فقط إلى الموقع. لا يحتاج Node ولا npm على السيرفر.
#
# السلامة:
#  - لا يلمس قاعدة البيانات ولا .env ولا storage ولا vendor ولا أي كود، ولا يشغّل migrate أو composer.
#  - يكتب فقط داخل public/build (مع نسخة public/build_old للتراجع) ثم php artisan view:clear.
#  - قفل (flock) يمنع تشغيلين متداخلين، والسجل في storage/logs/server-update.log.
#
# الإعداد (مرة واحدة):  REPO_URL=https://github.com/rowad-sy/rowad-app.git
#   إن كان المستودع خاصًا استخدم رابطًا فيه توكن قراءة فقط:  https://<TOKEN>@github.com/rowad-sy/rowad-app.git
# الاستخدام:  bash scripts/server-update.sh [--force]
set -u

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
REPO_URL="${REPO_URL:-https://github.com/rowad-sy/rowad-app.git}"
BRANCH="${BRANCH:-deploy}"
CACHE_DIR="${CACHE_DIR:-$HOME/rowad-deploy-cache}"
PHP_BIN="${PHP_BIN:-php}"
LOG="$APP_DIR/storage/logs/server-update.log"
FORCE=0; [ "${1:-}" = "--force" ] && FORCE=1

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }
mkdir -p "$(dirname "$LOG")" "$APP_DIR/storage/framework"
cd "$APP_DIR" || exit 1

exec 9>"$APP_DIR/storage/framework/server-update.lock"
flock -n 9 || { log "تخطّي: تشغيل آخر قيد التنفيذ"; exit 0; }

if [ ! -d "$CACHE_DIR/.git" ]; then
  log "أول تشغيل: استنساخ $BRANCH"
  rm -rf "$CACHE_DIR"
  git clone --quiet --depth 1 --branch "$BRANCH" "$REPO_URL" "$CACHE_DIR" >>"$LOG" 2>&1 || { log "خطأ: تعذّر الاستنساخ (المستودع خاص؟ استخدم REPO_URL مع توكن)"; exit 1; }
else
  git -C "$CACHE_DIR" fetch --quiet --depth 1 origin "$BRANCH" >>"$LOG" 2>&1 || { log "خطأ: تعذّر fetch"; exit 1; }
  git -C "$CACHE_DIR" reset --quiet --hard FETCH_HEAD
fi

NEW="$(git -C "$CACHE_DIR" rev-parse --short HEAD)"
SRC="$CACHE_DIR/public/build"
[ -f "$SRC/manifest.json" ] || { log "خطأ: فرع $BRANCH لا يحتوي public/build/manifest.json"; exit 1; }

if [ "$FORCE" -eq 0 ] && [ -f public/build/.deployed-from ] && [ "$(cat public/build/.deployed-from)" = "$NEW" ]; then
  exit 0   # لا جديد
fi

rm -rf public/build_old public/build_new
cp -a "$SRC" public/build_new && echo "$NEW" > public/build_new/.deployed-from || { log "خطأ: فشل النسخ"; rm -rf public/build_new; exit 1; }
[ -d public/build ] && mv public/build public/build_old
mv public/build_new public/build
"$PHP_BIN" artisan view:clear >>"$LOG" 2>&1
log "تم تحديث public/build إلى $NEW ($(ls public/build/assets | wc -l) ملف أصول)"
