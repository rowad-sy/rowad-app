#!/usr/bin/env bash
# تحديث الواجهة على السيرفر تلقائيًا: يسحب آخر main (fast-forward فقط) ثم يبني الأصول (npm run build) بنسخة Node من nodenv
# ثم يمسح كاش العروض. مخصص للتشغيل من Plesk (Scheduled Tasks) كل بضع دقائق أو يدويًا.
#
# السلامة:
#  - لا يلمس قاعدة البيانات ولا .env ولا storage ولا vendor، ولا يشغّل migrate أو composer.
#  - سحب fast-forward فقط: إن وُجدت تعديلات محلية متعارضة يتوقف ويسجّل خطأ دون كتابة فوقها.
#  - البناء يحتفظ بنسخة public/build_old ويستعيدها إن فشل البناء.
#  - قفل (flock) يمنع تشغيلين متداخلين، وسجل في storage/logs/server-update.log.
#
# الاستخدام:   bash scripts/server-update.sh            (يبني فقط إن تغيّر الكود أو غاب build)
#              bash scripts/server-update.sh --force    (يبني دائمًا)
set -u

APP_DIR="${APP_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
BRANCH="${BRANCH:-main}"
NODE_VERSION="${NODE_VERSION:-24}"
PHP_BIN="${PHP_BIN:-php}"
LOG="$APP_DIR/storage/logs/server-update.log"
FORCE=0; [ "${1:-}" = "--force" ] && FORCE=1

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*" >> "$LOG"; }
cd "$APP_DIR" || exit 1
mkdir -p "$(dirname "$LOG")"

exec 9>"$APP_DIR/storage/framework/server-update.lock"
flock -n 9 || { log "تخطّي: تشغيل آخر قيد التنفيذ"; exit 0; }

export NODENV_VERSION="$NODE_VERSION"
export PATH="$HOME/.nodenv/shims:$HOME/.nodenv/bin:$PATH"

if [ ! -d .git ]; then log "خطأ: $APP_DIR ليس مستودع git"; exit 1; fi

OLD_HEAD="$(git rev-parse HEAD)"
if ! git fetch --quiet origin "$BRANCH" 2>>"$LOG"; then log "خطأ: تعذّر git fetch"; exit 1; fi
NEW_HEAD="$(git rev-parse "origin/$BRANCH")"

if [ "$OLD_HEAD" != "$NEW_HEAD" ]; then
  log "تحديث الكود: ${OLD_HEAD:0:7} -> ${NEW_HEAD:0:7}"
  if ! git merge --ff-only "origin/$BRANCH" >>"$LOG" 2>&1; then
    log "خطأ: تعذّر fast-forward (تعديلات محلية متعارضة؟). لم يُغيَّر شيء. راجع: git status"
    exit 1
  fi
  CHANGED=1
else
  CHANGED=0
fi

if [ "$FORCE" -eq 0 ] && [ "$CHANGED" -eq 0 ] && [ -f public/build/manifest.json ]; then
  exit 0   # لا جديد
fi

log "بدء بناء الأصول (node $(node -v 2>/dev/null))"
if ! command -v npm >/dev/null 2>&1; then log "خطأ: npm غير متاح (تحقق من nodenv/NODE_VERSION)"; exit 1; fi

rm -rf public/build_old
[ -d public/build ] && cp -a public/build public/build_old

if npm ci --no-audit --no-fund >>"$LOG" 2>&1 && npm run build >>"$LOG" 2>&1 && [ -f public/build/manifest.json ]; then
  "$PHP_BIN" artisan view:clear >>"$LOG" 2>&1
  log "نجح البناء: $(ls public/build/assets | wc -l) ملف أصول"
else
  log "فشل البناء — استعادة النسخة السابقة"
  rm -rf public/build
  [ -d public/build_old ] && mv public/build_old public/build
  exit 1
fi
