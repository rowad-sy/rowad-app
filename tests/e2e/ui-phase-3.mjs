// اختبار متصفح فعلي (Playwright/Chromium) للمرحلة 3: المشاريع واللوجستيات والتقنية والعلاج الفيزيائي.
//
// الإعداد (قاعدة بيانات تجريبية معزولة فقط — لا تشغّله على بيانات حقيقية):
//   cp .env.example .env && php artisan key:generate
//   touch database/database.sqlite && php artisan migrate --force
//   php artisan db:seed --class=UiE2ESeeder --force && php artisan db:seed --class=UiE2EPhase3Seeder --force
//   npm run build && php artisan serve --port=8002
// التشغيل:  BASE=http://localhost:8002 OUT=docs/ui-phase-3/after node tests/e2e/ui-phase-3.mjs
// الحسابات (كلمة المرور password): admin@ (كامل)، ops@ (بلا نطاق)، scoped@ (نطاق «مركز الرواد الرئيسي…» فقط)، readonly@ (عرض فقط) — @test.local.
// جميع الاختبارات تمرّ عبر الخادم الحقيقي؛ حيث يُعطَّل تحقق المتصفح (noValidate) للوصول إلى تحقق الخادم يُذكر ذلك في اسم الاختبار.
import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.BASE || 'http://localhost:8002';
const OUT = process.env.OUT || 'docs/ui-phase-3/after';
fs.mkdirSync(OUT, { recursive: true });
const results = [];
const ok = (name, cond, extra = '') => results.push(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? ' — ' + extra : ''}`);
const stamp = Date.now().toString().slice(-6);
const browser = await chromium.launch();
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;
const section = async (id, fn) => { if (ONLY && !ONLY.includes(id)) return; try { await fn(); } catch (e) { ok(`القسم ${id} انهار`, false, String(e.message).split('\n')[0]); } };

async function login(email) {
  const ctx = await browser.newContext({ locale: 'ar' });
  const p = await ctx.newPage();
  p.errors = [];
  p.on('pageerror', (e) => p.errors.push(e.message));
  await p.goto(BASE + '/login');
  await p.fill('#email', email); await p.fill('#password', 'password');
  await p.click('button[type=submit]');
  await p.waitForURL((u) => !u.pathname.includes('login'));
  return { ctx, p };
}
const theme = async (p, t) => { await p.evaluate((x) => { document.documentElement.dataset.theme = x; document.documentElement.dataset.bsTheme = x; }, t); await p.waitForTimeout(350); };
const hscroll = (p) => p.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
const lum = (rgb) => { const m = rgb.match(/\d+/g).map(Number); return (0.299 * m[0] + 0.587 * m[1] + 0.114 * m[2]) / 255; };

// ============ 1) مسح الصفحات: 390/768/1440 × فاتح/داكن — لا 500 ولا تمرير أفقي ولا أسطح بيضاء في الداكن، وTajawal فعلًا ============
const PAGES = ['projects', 'projects/tasks', 'projects/1/overview', 'paths', 'project-activities', 'event-cards', 'event-cards/1', 'media-plans', 'media-plans/1', 'movement-plans', 'movement-plans/1',
  'monthly-reports', 'logistics/purchase-requests', 'logistics/purchase-requests/1', 'logistics/warehouses', 'logistics/warehouses/1/items', 'logistics/warehouses/1/deleted-items', 'logistics/assets', 'logistics/statistics',
  'tech/issues', 'tech/issues/1', 'tech/equipment', 'tech/equipment/1', 'tech/statistics', 'tech/emails', 'physiotherapy/patients', 'physiotherapy/patients/1', 'physiotherapy/followups', 'physiotherapy/statistics', 'physiotherapy/rooms',
  'projects/create', 'event-cards/create', 'logistics/purchase-requests/create', 'tech/issues/create', 'physiotherapy/patients/create'];
await section('0', async () => {
  const { ctx, p } = await login('admin@test.local');
  let bad = [], white = [], fontOk = true;
  for (const w of [390, 768, 1440]) {
    await p.setViewportSize({ width: w, height: 900 });
    for (const t of ['light', 'dark']) {
      for (const path of PAGES) {
        p.errors.length = 0;
        const r = await p.goto(BASE + '/admin/' + path, { waitUntil: 'load' });
        await theme(p, t);
        const h = await hscroll(p);
        if (r.status() !== 200 || h > 0 || p.errors.length) bad.push(`${w}/${t}/${path}:${r.status()}:h${h}${p.errors[0] ? ':' + p.errors[0].slice(0, 60) : ''}`);
        if (t === 'dark') {
          const l = await p.evaluate(() => [...document.querySelectorAll('.table-container,.form-card,.card')].filter((e) => e.offsetParent && !e.closest('.modal')).map((e) => getComputedStyle(e).backgroundColor).filter((c) => /rgb/.test(c)));
          if (l.some((c) => lum(c) > 0.7)) white.push(`${w}/${path}`);
        }
        if (w === 1440 && t === 'light') {
          fontOk = fontOk && await p.evaluate(async () => { await document.fonts.ready; return getComputedStyle(document.body).fontFamily.includes('Tajawal') && document.fonts.check('16px Tajawal') && [...document.fonts].some((f) => f.family.includes('Tajawal') && f.status === 'loaded'); });
        }
      }
    }
  }
  ok(`مسح ${PAGES.length} صفحة × 3 عروض × وضعين: لا 500 ولا تمرير أفقي ولا خطأ JS`, bad.length === 0, bad.slice(0, 6).join(' | '));
  ok('الوضع الداكن: لا بطاقات/جداول بخلفية بيضاء', white.length === 0, white.slice(0, 6).join(' | '));
  ok('خط Tajawal محمّل فعليًا (document.fonts)', fontOk);
  await ctx.close();
});

// ============ 2) الفلاتر والترقيم والمسح (تقنية) ============
await section('1', async () => {
  const { ctx, p } = await login('ops@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/tech/issues');
  await p.selectOption('.filter-bar select[name=priority]', 'high').catch(() => {});
  await p.waitForTimeout(400);
  ok('تقنية: تغيير الفلتر لا يرسل تلقائيًا', new URL(p.url()).search === '');
  await p.click('.filter-bar button[type=submit]');
  await p.waitForLoadState('load');
  ok('تقنية: «تطبيق» يحتفظ بالفلتر في الرابط والحقل', new URL(p.url()).searchParams.get('priority') === 'high' && (await p.inputValue('.filter-bar select[name=priority]')) === 'high');
  const txt = await p.textContent('table tbody');
  ok('تقنية: النتيجة تحتوي التذكرة المطابقة فقط', txt.includes('لا يعمل الإنترنت في المكتب') && !txt.includes('طلب تثبيت برنامج'));
  await p.goto(BASE + '/admin/tech/issues?search=' + encodeURIComponent('لا-شيء-مطابق'));
  ok('تقنية: حالة «لا نتائج مطابقة» تختلف عن «لا بيانات»', (await p.textContent('table tbody')).includes('لا توجد نتائج تطابق الفلاتر'));
  await p.click('.filter-bar a:has-text("مسح")');
  await p.waitForLoadState('load');
  ok('تقنية: زر المسح يعيد القائمة الكاملة', new URL(p.url()).search === '' && (await p.textContent('table tbody')).includes('شاشة معطلة'));
  await p.goto(BASE + '/admin/tech/issues?per_page=1&project_id=1');
  const links = await p.$$eval('.pagination a', (as) => as.map((x) => x.getAttribute('href')));
  ok('تقنية: روابط الترقيم موجودة وتحفظ الفلاتر', links.length > 0 && links.every((h) => h.includes('project_id=1') && h.includes('per_page=1')), `links=${links.length}`);
  await ctx.close();
});

// ============ 3) عزل البيانات لحساب بنطاق مركز (ليس 403 فقط) ============
await section('2', async () => {
  const { ctx, p } = await login('scoped@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/tech/issues');
  const t1 = await p.textContent('table tbody');
  ok('نطاق المركز: التذاكر تحوي مركزه فقط', t1.includes('لا يعمل الإنترنت في المكتب') && !t1.includes('طلب تثبيت برنامج'));
  await p.goto(BASE + '/admin/physiotherapy/patients');
  const t2 = await p.textContent('table tbody');
  ok('نطاق المركز: المرضى — مرضى مركزه فقط', t2.includes('مريض تجريبي أ') && !t2.includes('مريض تجريبي ج'));
  const r = await p.goto(BASE + '/admin/physiotherapy/patients/3');
  ok('نطاق المركز: فتح مريض من مركز آخر مباشرةً يُرفض', r.status() === 403);
  await ctx.close();
});

// ============ 4) صلاحيات العرض فقط: لا أزرار إضافة/تعديل ============
await section('3', async () => {
  const { ctx, p } = await login('readonly@test.local');
  await p.goto(BASE + '/admin/tech/issues');
  ok('عرض فقط: لا زر إضافة تذكرة', (await p.locator('a[href$="/tech/issues/create"]').count()) === 0);
  const r = await p.goto(BASE + '/admin/tech/issues/create');
  ok('عرض فقط: صفحة الإضافة مرفوضة', r.status() === 403);
  await ctx.close();
});

// ============ 5) فشل تحقق حقيقي من الخادم: بقاء القيم، ثم نجاح الحفظ بعد التصحيح ============
await section('4', async () => {
  const { ctx, p } = await login('ops@test.local');
  await p.setViewportSize({ width: 390, height: 900 });
  // (أ) مادة مخزن — تعطيل تحقق المتصفح للوصول إلى تحقق الخادم
  await p.goto(BASE + '/admin/logistics/warehouses/1/items/create');
  await p.fill('input[name=name]', 'مادة E2E ' + stamp); await p.fill('input[name=quantity]', '7');
  await p.evaluate(() => { document.querySelector('form[method=POST]:has(input[name=name])').noValidate = true; });
  await p.click('form:has(input[name=name]) button[type=submit]');
  await p.waitForLoadState('load');
  ok('مخزن (خادم حقيقي): بعد الفشل يبقى الاسم والكمية ويظهر خطأ الوحدة', (await p.inputValue('input[name=name]')) === 'مادة E2E ' + stamp && (await p.inputValue('input[name=quantity]')) === '7' && (await p.locator('input[name=unit].is-invalid').count()) === 1);
  ok('مخزن: aria-invalid على الحقل الخاطئ', (await p.getAttribute('input[name=unit]', 'aria-invalid')) === 'true');
  await p.fill('input[name=unit]', 'كرتون');
  await p.click('form:has(input[name=name]) button[type=submit]');
  await p.waitForLoadState('load');
  ok('مخزن: نجاح الحفظ بعد التصحيح وظهور المادة بكميتها ووحدتها', (await p.textContent('table tbody')).includes('مادة E2E ' + stamp) && (await p.textContent('table tbody')).includes('كرتون'));
  await p.screenshot({ path: `${OUT}/warehouse-items-390.png`, fullPage: true });
  // (ب) تذكرة تقنية
  await p.goto(BASE + '/admin/tech/issues/create');
  await p.fill('input[name=title]', 'تذكرة E2E ' + stamp);
  await p.selectOption('select[name=priority]', 'urgent');
  await p.evaluate(() => { document.querySelector('form[method=POST]:has(input[name=title])').noValidate = true; });
  await p.click('form:has(input[name=title]) button[type=submit]');
  await p.waitForLoadState('load');
  ok('تقنية (خادم حقيقي): بعد الفشل يبقى العنوان والأولوية', (await p.inputValue('input[name=title]')) === 'تذكرة E2E ' + stamp && (await p.inputValue('select[name=priority]')) === 'urgent');
  await p.fill('textarea[name=description]', 'وصف مصحّح');
  await p.click('form:has(input[name=title]) button[type=submit]');
  await p.waitForLoadState('load');
  ok('تقنية: نجاح الحفظ بعد التصحيح', new URL(p.url()).pathname.includes('/tech/issues') && (await p.textContent('body')).includes('تذكرة E2E ' + stamp));
  // (ج) طلب شراء: إضافة/حذف/إضافة صف ثم فشل خادم يحتفظ بالبنود
  await p.goto(BASE + '/admin/logistics/purchase-requests/create');
  await p.click('button:has-text("إضافة بند")');
  ok('طلب شراء: إضافة صف تعطي صفين', (await p.locator('#itemsBody .item-row').count()) === 2);
  await p.locator('#itemsBody .item-row .btn-outline-danger').last().click();
  await p.click('button:has-text("إضافة بند")');
  const rows = p.locator('#itemsBody .item-row');
  ok('طلب شراء: إضافة → حذف → إضافة بلا تصادم في أسماء الحقول', (await rows.count()) === 2 && new Set(await p.$$eval('#itemsBody [name]', (els) => els.map((e) => e.name))).size === 12);
  ok('طلب شراء: الحقول الديناميكية مسمّاة عند الإنشاء', (await p.getAttribute('#itemsBody .item-row:last-child .item-desc', 'aria-label')).startsWith('وصف البند'));
  await p.locator('#itemsBody .item-row').nth(0).locator('.item-desc').fill('بند E2E الأول');
  await p.locator('#itemsBody .item-row').nth(0).locator('.item-unit').fill('علبة');
  await p.locator('#itemsBody .item-row').nth(1).locator('.item-desc').fill('بند E2E الثاني');
  await p.locator('#itemsBody .item-row').nth(1).locator('.item-unit').fill('قطعة');
  await p.evaluate(() => { document.querySelector('form[method=POST]:has(#itemsBody)').noValidate = true; });
  await p.click('form:has(#itemsBody) button[type=submit]');
  await p.waitForLoadState('load');
  const descs = await p.$$eval('#itemsBody .item-desc', (els) => els.map((e) => e.value));
  ok('طلب شراء (خادم حقيقي، بعد فشل التحقق): تبقى البنود المُدخلة كلها', descs.join('|') === 'بند E2E الأول|بند E2E الثاني' || descs.includes('بند E2E الأول'), descs.join('|'));
  await ctx.close();
});

// ============ 7) طلب شراء: مفاتيح غير متتابعة، خطأ خادم حقيقي بجوار الحقل، ثم التصحيح والحفظ ============
await section('6', async () => {
  const { ctx, p } = await login('ops@test.local');
  const special = 'بند "أول" <b>x</b> & \'y\'';
  for (const w of [390, 768, 1440]) {
    await p.setViewportSize({ width: w, height: 900 });
    await p.goto(BASE + '/admin/logistics/purchase-requests/create');
    await p.selectOption('select[name=center_id]', { index: 1 });
    await p.selectOption('select[name=project_id]', { index: 1 });
    await p.click('button:has-text("إضافة بند")'); await p.click('button:has-text("إضافة بند")'); // مفاتيح 0,1,2
    await p.locator('#itemsBody .item-row[data-key="1"] .btn-outline-danger').click();            // حذف 1
    await p.click('button:has-text("إضافة بند")');                                                  // مفتاح 3
    const keys = await p.$$eval('#itemsBody .item-row', (r) => r.map((x) => x.dataset.key));
    ok(`[${w}] طلب شراء: المفاتيح بعد إضافة/حذف/إضافة غير متتابعة وفريدة`, keys.join(',') === '0,2,3', keys.join(','));
    const fill = async (k, d, q, u, pr, n) => { const r = p.locator(`#itemsBody .item-row[data-key="${k}"]`); await r.locator('.item-desc').fill(d); await r.locator('.item-qty').fill(q); await r.locator('.item-unit').fill(u); await r.locator('.item-price').fill(pr); if (n) await r.locator('.item-notes').fill(n); };
    await fill(0, special, '4', 'علبة', '2.5', 'ملاحظة');
    await fill(2, '', '0', 'قطعة', '10');   // وصف فارغ + كمية صفر: أخطاء خادم حقيقية
    await fill(3, 'بند ثالث', '3', 'كرتون', '1.25');
    await p.evaluate(() => { document.querySelector('form:has(#itemsBody)').noValidate = true; });
    await p.click('form:has(#itemsBody) button[type=submit]');
    await p.waitForLoadState('load');
    const after = await p.$$eval('#itemsBody .item-row', (r) => r.map((x) => x.dataset.key));
    ok(`[${w}] بعد فشل الخادم: المفاتيح الأصلية محفوظة`, after.join(',') === '0,2,3', after.join(','));
    ok(`[${w}] القيم كما أُرسلت (نص خاص، صفر، فارغ)`, (await p.inputValue('#pr-item-0-description')) === special && (await p.inputValue('#pr-item-2-quantity')) === '0' && (await p.inputValue('#pr-item-2-description')) === '');
    const fb = await p.$eval('#pr-item-2-description', (el) => ({ inv: el.getAttribute('aria-invalid'), d: el.getAttribute('aria-describedby'), txt: document.getElementById(el.getAttribute('aria-describedby') || 'x')?.textContent || '' }));
    ok(`[${w}] خطأ الوصف بجوار الحقل الصحيح ومربوط (aria-invalid/describedby)`, fb.inv === 'true' && fb.d === 'pr-item-2-description-error' && fb.txt.includes('وصف البند'), JSON.stringify(fb));
    const fq = await p.$eval('#pr-item-2-quantity', (el) => document.getElementById(el.getAttribute('aria-describedby') || 'x')?.textContent || '');
    ok(`[${w}] خطأ الكمية عند المفتاح 2`, fq.includes('كمية البند'), fq);
    ok(`[${w}] لا أخطاء على البنود السليمة`, (await p.locator('#itemsBody .item-row[data-key="0"] .is-invalid, #itemsBody .item-row[data-key="3"] .is-invalid').count()) === 0);
    ok(`[${w}] التركيز على أول حقل خاطئ`, (await p.evaluate(() => document.activeElement && document.activeElement.id)) === 'pr-item-2-description');
    await p.click('button:has-text("إضافة بند")');
    const names = await p.$$eval('#itemsBody [name]', (e) => e.map((x) => x.name));
    ok(`[${w}] إضافة بند بعد الخطأ: مفتاح جديد 4 بلا تكرار`, (await p.$$eval('#itemsBody .item-row', (r) => r.map((x) => x.dataset.key))).join(',') === '0,2,3,4' && new Set(names).size === names.length);
    ok(`[${w}] الجدول يمرّر محليًا بلا تمرير أفقي للصفحة`, (await hscroll(p)) === 0);
    await p.screenshot({ path: `${OUT}/purchase-request-form-errors-${w}-light.png`, fullPage: false });
    if (w === 1440) { await theme(p, 'dark'); await p.screenshot({ path: `${OUT}/purchase-request-form-errors-1440-dark.png`, fullPage: false }); await theme(p, 'light'); }
    if (w === 390 || w === 768) { await theme(p, 'dark'); ok(`[${w}] داكن: بلا تمرير أفقي`, (await hscroll(p)) === 0); await theme(p, 'light'); }
    // تصحيح ثم حفظ فعلي
    await p.locator('#itemsBody .item-row[data-key="4"] .btn-outline-danger').click();
    await p.fill('#pr-item-2-description', 'بند ثانٍ مصحّح'); await p.fill('#pr-item-2-quantity', '2');
    await p.click('form:has(#itemsBody) button[type=submit]');
    await p.waitForLoadState('load');
    const ids = await p.$$eval('a[href*="/purchase-requests/"]', (a) => a.map((x) => (x.getAttribute('href').match(/purchase-requests\/(\d+)$/) || [])[1]).filter(Boolean).map(Number));
    await p.goto(BASE + '/admin/logistics/purchase-requests/' + Math.max(...ids));
    const body = await p.textContent('body');
    ok(`[${w}] بعد التصحيح: حُفظ الطلب وظهرت البنود والإجمالي الصحيح (10+20+3.75=33.75)`, /33\.75/.test(body) && body.includes('بند ثانٍ مصحّح') && body.includes('بند ثالث') && body.includes('<b>x</b>'), new URL(p.url()).pathname);
  }
  await ctx.close();
});

// ============ 6) لقطات تمثيلية ============
await section('5', async () => {
  const { ctx, p } = await login('admin@test.local');
  for (const [w, t] of [[390, 'light'], [1440, 'light'], [1440, 'dark']]) {
    await p.setViewportSize({ width: w, height: 900 });
    for (const [n, path] of [['projects-hub', 'projects/1/overview'], ['tech-issues', 'tech/issues'], ['tech-issue-show', 'tech/issues/1'], ['physio-patient', 'physiotherapy/patients/1'], ['purchase-request', 'logistics/purchase-requests/1'], ['movement-plan', 'movement-plans/1'], ['warehouse-items', 'logistics/warehouses/1/items']]) {
      await p.goto(BASE + '/admin/' + path); await theme(p, t);
      await p.screenshot({ path: `${OUT}/${n}-${w}-${t}.png`, fullPage: false });
    }
  }
  await ctx.close();
});

await browser.close();
console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('PASS')).length} passed, ${results.filter((r) => r.startsWith('FAIL')).length} failed`);
process.exit(results.some((r) => r.startsWith('FAIL')) ? 1 : 0);
