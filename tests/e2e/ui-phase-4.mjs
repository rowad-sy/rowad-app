// اختبار متصفح فعلي (Chromium) للمرحلة 4: مسح شامل لصفحات GET الفعلية بلا معاملات + فحوص تركيز/حوارات/تبويبات/صفحات الأخطاء.
// إعادة التشغيل: docs/ui-phase-4/README.md (قاعدة تجريبية معزولة فقط). BASE=http://localhost:8002 OUT=docs/ui-phase-4/after node tests/e2e/ui-phase-4.mjs
// ملاحظة: مسح الصفحات يثبت الفتح/التخطيط/الأخطاء فقط، ولا يختبر عمليات الحفظ (تُغطّى باختبارات Pest HTTP والأقسام 2–5 أدناه).
import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.BASE || 'http://localhost:8002';
const OUT = process.env.OUT || 'docs/ui-phase-4/after';
const ONLY = process.env.ONLY ? process.env.ONLY.split(',') : null;
fs.mkdirSync(OUT, { recursive: true });
const results = [];
const ok = (name, cond, extra = '') => results.push(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? ' — ' + extra : ''}`);
const browser = await chromium.launch();
const section = async (id, fn) => { if (ONLY && !ONLY.includes(id)) return; try { await fn(); } catch (e) { ok(`القسم ${id} انهار`, false, String(e.message).split('\n')[0]); } };
const theme = async (p, t) => { await p.evaluate((x) => { document.documentElement.dataset.theme = x; document.documentElement.dataset.bsTheme = x; }, t); await p.waitForTimeout(300); };
const hscroll = (p) => p.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
const lum = (rgb) => { const m = rgb.match(/\d+/g).map(Number); return (0.299 * m[0] + 0.587 * m[1] + 0.114 * m[2]) / 255; };

async function ctxFor(email) {
  const ctx = await browser.newContext({ locale: 'ar' });
  const p = await ctx.newPage();
  p.errs = []; p.bad = []; p.ext = new Set();
  p.on('pageerror', (e) => p.errs.push(e.message));
  p.on('console', (m) => { if (m.type() === 'error') p.errs.push('console: ' + m.text().slice(0, 100)); });
  p.on('requestfailed', (r) => p.bad.push('failed ' + r.url().slice(0, 90)));
  p.on('response', (r) => { const u = new URL(r.url()); if (u.origin === new URL(BASE).origin && r.status() >= 400 && r.request().resourceType() !== 'document') p.bad.push(r.status() + ' ' + u.pathname); });
  p.on('request', (r) => { const u = new URL(r.url()); if (u.origin !== new URL(BASE).origin && !u.protocol.startsWith('data')) p.ext.add(u.host); });
  if (email) {
    await p.goto(BASE + '/login'); await p.fill('#email', email); await p.fill('#password', 'password');
    await p.click('button[type=submit]'); await p.waitForURL((u) => !u.pathname.includes('login'));
  }
  return { ctx, p };
}

const ADMIN_PAGES = `admin admin/portal admin/dashboard admin/profile admin/identities admin/audit-logs admin/centers admin/centers/create admin/cohorts admin/cohorts/create admin/departments admin/departments/create admin/groups admin/groups/create
admin/permissions admin/permissions/create admin/users admin/users/create admin/password/change admin/hr/attendances admin/hr/employees admin/hr/employees/create admin/hr/employees/statistics admin/hr/job-positions admin/hr/job-positions/create admin/hr/leave-approvals admin/hr/leave-policies admin/hr/leave-requests admin/hr/timesheets
admin/students admin/students/create admin/students/attendance admin/students/courses admin/students/courses/create admin/students/courses/help admin/students/levels admin/students/periods admin/students/training-plans admin/students/statistics admin/students/certificates admin/students/certificates/designs admin/students/certificates/issue admin/students/certificates/signatory-sets admin/students/certificates/signers
admin/projects admin/projects/create admin/projects/statistics admin/projects/calendar admin/projects/tasks admin/paths admin/paths/tree admin/project-activities admin/event-cards admin/events-calendar admin/media-plans admin/movement-plans admin/monthly-reports admin/project-docs/documents admin/project-docs/templates admin/projects-manager admin/project-manager admin/project-officer
admin/logistics/purchase-requests admin/logistics/purchase-requests/create admin/logistics/purchase-requests/help admin/logistics/warehouses admin/logistics/warehouses/1/items admin/logistics/warehouses/1/deleted-items admin/logistics/assets admin/logistics/assets/create admin/logistics/approval-rules admin/logistics/settings admin/logistics/statistics
admin/tech/issues admin/tech/issues/1 admin/tech/equipment admin/tech/statistics admin/tech/emails admin/physiotherapy/patients admin/physiotherapy/patients/1 admin/physiotherapy/followups admin/physiotherapy/transfers admin/physiotherapy/rooms admin/physiotherapy/statistics`.split(/\s+/);
const GUEST_PAGES = ['login', 'choose', 'login/employee', 'login/beneficiary', 'register', 'register/employee', 'forgot-password', 'no-such-page-404'];

// 1) مسح المدير (كل الصفحات): 3 عروض × وضعين
await section('1', async () => {
  const { ctx, p } = await ctxFor('admin@test.local');
  const bad = [], white = [], fonts = [], ext = new Set(), reqbad = [];
  for (const w of [390, 768, 1440]) {
    await p.setViewportSize({ width: w, height: 900 });
    for (const t of ['light', 'dark']) for (const path of ADMIN_PAGES) {
      p.errs.length = 0; p.bad.length = 0;
      const r = await p.goto(BASE + '/' + path, { waitUntil: 'load' });
      await theme(p, t);
      const h = await hscroll(p);
      const st = r.status();
      if (st !== 200 || h > 0 || p.errs.length) bad.push(`${w}/${t}/${path}:${st}:h${h}${p.errs[0] ? ':' + p.errs[0].slice(0, 50) : ''}`);
      if (p.bad.length) reqbad.push(`${w}/${path}:${p.bad[0]}`);
      if (t === 'dark') { const l = await p.evaluate(() => [...document.querySelectorAll('.table-container,.form-card,.card')].filter((e) => e.offsetParent && !e.closest('.modal')).map((e) => getComputedStyle(e).backgroundColor).filter((c) => /rgb/.test(c))); if (l.some((c) => lum(c) > 0.7)) white.push(`${w}/${path}`); }
      if (w === 1440 && t === 'light') { const f = await p.evaluate(async () => { await document.fonts.ready; return getComputedStyle(document.body).fontFamily.includes('Tajawal') && [...document.fonts].some((x) => x.family.includes('Tajawal') && x.status === 'loaded'); }); if (!f) fonts.push(path); }
    }
  }
  p.ext.forEach((h) => ext.add(h));
  ok(`مسح المدير: ${ADMIN_PAGES.length} صفحة × 3 عروض × وضعين بلا 500/تمرير أفقي/خطأ JS`, bad.length === 0, bad.slice(0, 8).join(' | '));
  ok('مسح المدير: لا طلبات موارد فاشلة (≥400 أو فشل شبكة)', reqbad.length === 0, reqbad.slice(0, 5).join(' | '));
  ok('الوضع الداكن: لا بطاقات/جداول بخلفية بيضاء', white.length === 0, white.slice(0, 6).join(' | '));
  ok('Tajawal محلي محمّل فعليًا في كل الصفحات', fonts.length === 0, fonts.slice(0, 5).join(','));
  ok('لا طلبات لمضيفين خارجيين في صفحات الإدارة', ext.size === 0, [...ext].join(','));
  await ctx.close();
});

// 2) صفحات الضيف والدخول والأخطاء (بيانات فارغة/بلا جلسة)
await section('2', async () => {
  const { ctx, p } = await ctxFor(null);
  const bad = [], ext = new Set();
  for (const w of [390, 768, 1440]) { await p.setViewportSize({ width: w, height: 900 });
    for (const t of ['light', 'dark']) for (const path of GUEST_PAGES) {
      p.errs.length = 0;
      const r = await p.goto(BASE + '/' + path, { waitUntil: 'load' }); await theme(p, t);
      const h = await hscroll(p);
      const exp = path === 'no-such-page-404' ? 404 : 200;
      const errs = exp === 404 ? p.errs.filter((x) => !x.includes('Failed to load resource')) : p.errs; // سطر المتصفح الذاتي لحالة 404 المقصودة
      if (r.status() !== exp || h > 0 || errs.length) bad.push(`${w}/${t}/${path}:${r.status()}:h${h}${errs[0] ? ':' + errs[0].slice(0, 50) : ''}`);
    } }
  p.ext.forEach((h) => ext.add(h));
  ok('صفحات الضيف والدخول و404: تُفتح بلا تمرير أفقي أو أخطاء (3 عروض × وضعين)', bad.length === 0, bad.slice(0, 6).join(' | '));
  ok('صفحات الضيف: لا طلبات لمضيفين خارجيين (الخط محلي)', ext.size === 0, [...ext].join(','));
  await p.goto(BASE + '/no-such-page-404');
  ok('404: نص عربي ورابط للعودة/الرئيسية ولا زر خروج للضيف', (await p.locator('text=الصفحة غير موجودة').count()) === 1 && (await p.locator('a:has-text("الصفحة الرئيسية")').count()) === 1 && (await p.locator('button:has-text("تسجيل الخروج")').count()) === 0);
  await p.setViewportSize({ width: 390, height: 800 }); await p.screenshot({ path: `${OUT}/error-404-390-light.png` });
  await p.goto(BASE + '/admin/hr/employees'); ok('ضيف يفتح /admin: يُعاد إلى الدخول', new URL(p.url()).pathname.includes('login'));
  await ctx.close();
});

// 3) 403 لحساب مصادَق: مسار عودة وخروج بلا وصول إضافي
await section('3', async () => {
  const { ctx, p } = await ctxFor('readonly@test.local');
  const r = await p.goto(BASE + '/admin/hr/employees');
  ok('حساب بلا صلاحية HR: الخادم يرفض بـ403 (لا اختفاء أزرار فقط)', r.status() === 403);
  ok('403: رسالة عربية + عودة + الذهاب للتطبيقات + زر تسجيل الخروج', (await p.locator('text=ليس لديك صلاحية').count()) === 1 && (await p.locator('button:has-text("تسجيل الخروج")').count()) === 1 && (await p.locator('a:has-text("الذهاب للتطبيقات")').count()) === 1);
  for (const [w, t] of [[390, 'light'], [1440, 'dark']]) { await p.setViewportSize({ width: w, height: 800 }); await theme(p, t); ok(`403 ${w}/${t}: بلا تمرير أفقي`, (await hscroll(p)) === 0); await p.screenshot({ path: `${OUT}/error-403-${w}-${t}.png` }); }
  await p.click('button:has-text("تسجيل الخروج")');
  await p.waitForLoadState('load');
  const r2 = await p.goto(BASE + '/admin/tech/issues');
  ok('بعد الخروج من صفحة 403: الجلسة منتهية ويُعاد إلى الدخول ولا وصول', new URL(p.url()).pathname.includes('login'), String(r2.status()));
  await ctx.close();
});

// 4) لوحة المفاتيح والحوارات والتبويبات
await section('4', async () => {
  const { ctx, p } = await ctxFor('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/logistics/warehouses/1/items');
  const trigger = p.locator('button[aria-label="حذف"]').first();
  await trigger.focus(); await p.keyboard.press('Enter');
  await p.waitForSelector('.modal.show'); await p.waitForTimeout(600);
  const inModal = await p.evaluate(() => !!document.activeElement.closest('.modal'));
  ok('حوار الحذف: التركيز ينتقل داخل الحوار عند الفتح', inModal, await p.evaluate(() => document.activeElement && (document.activeElement.tagName + '.' + document.activeElement.className)));
  await p.keyboard.press('Escape');
  await p.waitForTimeout(700);
  const back = await p.evaluate(() => document.activeElement && document.activeElement.getAttribute('aria-label'));
  ok('حوار الحذف: بعد الإغلاق يعود التركيز إلى زر الفتح', back === 'حذف', String(back));
  // تبويب فيه خطأ يُفتح تلقائيًا: نموذج الطالب (تبويبات) بإرسال فارغ إلى الخادم الحقيقي
  const admin = await ctxFor('admin@test.local');
  const q = admin.p; await q.setViewportSize({ width: 1440, height: 900 });
  await q.goto(BASE + '/admin/students/create');
  await q.evaluate(() => { document.querySelector('form[method=POST]:has(input[name=student_code])').noValidate = true; });
  await q.click('form:has(input[name=student_code]) button[type=submit]');
  await q.waitForLoadState('load');
  const act = await q.evaluate(() => { const a = document.activeElement; return { id: a && a.id, inPane: a && a.closest('.tab-pane') ? a.closest('.tab-pane').classList.contains('active') : null, invalid: a && a.classList.contains('is-invalid') }; });
  ok('نموذج بتبويبات (خادم حقيقي): التركيز على أول حقل خاطئ داخل تبويب نشط', act.invalid === true && act.inPane !== false, JSON.stringify(act));
  await admin.ctx.close(); await ctx.close();
});

// 5) لقطات ممثلة للمواضع المعدلة في هذه المرحلة
await section('5', async () => {
  const { ctx, p } = await ctxFor('admin@test.local');
  for (const [w, t] of [[390, 'light'], [1440, 'light'], [1440, 'dark']]) { await p.setViewportSize({ width: w, height: 900 });
    for (const [n, path] of [['profile', 'admin/profile'], ['audit-logs', 'admin/audit-logs'], ['identities', 'admin/identities'], ['tasks-statistics', 'admin/projects/statistics'], ['students-statistics', 'admin/students/statistics']]) {
      const r = await p.goto(BASE + '/' + path); await theme(p, t); if (r.status() === 200) await p.screenshot({ path: `${OUT}/${n}-${w}-${t}.png` }); } }
  await ctx.close();
});

await browser.close();
console.log(results.join('\n'));
console.log(`\n${results.filter((r) => r.startsWith('PASS')).length} passed, ${results.filter((r) => r.startsWith('FAIL')).length} failed`);
process.exit(results.some((r) => r.startsWith('FAIL')) ? 1 : 0);
