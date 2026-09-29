// اختبار متصفح فعلي (Playwright/Chromium) للمرحلة 1. تشغيل:
//   php artisan serve --port=8002   (بقاعدة بيانات تجريبية معزولة فيها المستخدمون أدناه)
//   BASE=http://localhost:8002 node tests/e2e/ui-phase-1.mjs
// المستخدمون: admin@test.local (سوبر أدمن)، limited@test.local (محدود)، pma@test.local (مدير مشاريع وله طلبات توقيع)،
// pmb@test.local (مدير مشاريع بلا إجراءات). كلمة المرور password. لا يتجاوز الاختبار أي صلاحية أو middleware.
import { chromium } from 'playwright';

const BASE = process.env.BASE || 'http://localhost:8002';
const OUT = process.env.OUT || 'docs/ui-phase-1/after';
const results = [];
const ok = (name, cond, extra = '') => { results.push(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? ' — ' + extra : ''}`); };

const browser = await chromium.launch();
async function login(email) {
  const ctx = await browser.newContext({ locale: 'ar' });
  const p = await ctx.newPage();
  const fontReq = [];
  p.on('response', (r) => { if (r.url().includes('/fonts/tajawal/')) fontReq.push(`${r.status()} ${r.url().split('/').pop()}`); });
  await p.goto(BASE + '/login');
  await p.fill('#email', email); await p.fill('#password', 'password');
  await p.click('button[type=submit]');
  await p.waitForURL((u) => !u.pathname.includes('login'));
  p.fontReq = fontReq;
  return { ctx, p };
}
const active = (p) => p.evaluate(() => { const a = document.activeElement; return { id: a.id, cls: a.className, href: a.getAttribute && a.getAttribute('href'), inside: !!a.closest('#sidebar'), visible: a.getClientRects().length > 0 }; });

// ---------- الخط ----------
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin');
  await p.evaluate(() => document.fonts.ready);
  const fonts = await p.evaluate(() => [...document.fonts].filter((f) => f.family.includes('Tajawal') && f.status === 'loaded').map((f) => `${f.weight}`));
  ok('Tajawal محمّل فعليًا (FontFace status=loaded)', fonts.length > 0, 'weights=' + fonts.join(','));
  ok('طلبات ملفات الخط المحلية 200', p.fontReq.length > 0 && p.fontReq.every((x) => x.startsWith('200')), p.fontReq.join(' | '));
  ok('body يستخدم Tajawal', (await p.evaluate(() => getComputedStyle(document.body).fontFamily)).includes('Tajawal'));
  await ctx.close();
}

// ---------- قائمة الجوال + حصر التركيز ----------
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 390, height: 844 });
  await p.goto(BASE + '/admin/users');
  await p.click('#sidebarToggle');
  await p.waitForTimeout(300);
  const heads = await p.$$('#sidebar .nav-section');
  await heads[heads.length - 1].click(); // طيّ القسم الأخير
  await p.waitForTimeout(100);
  const list = await p.evaluate(() => {
    const sb = document.getElementById('sidebar');
    return [...sb.querySelectorAll('a[href], button')].filter((e) => e.getClientRects().length && !e.disabled && e.getAttribute('tabindex') !== '-1').length;
  });
  // نبدأ من أول عنصر ونضغط Tab (n+2) مرة: يجب ألا يخرج التركيز ولا يقع على عنصر مخفي
  await p.evaluate(() => document.querySelector('#sidebar a[href]').focus());
  let bad = 0; const seen = new Set();
  for (let i = 0; i < list + 3; i++) {
    await p.keyboard.press('Tab');
    const a = await active(p);
    if (!a.inside || !a.visible) bad++;
    seen.add(a.href || a.cls);
  }
  ok('Tab يدور داخل القائمة ولا يقع على مخفي بعد طيّ القسم الأخير', bad === 0, `focusable=${list}, bad=${bad}`);
  // من آخر عنصر (عنوان القسم المطوي) → Tab يعود للأول
  await p.evaluate(() => { const h = [...document.querySelectorAll('#sidebar .nav-section')].pop(); h.focus(); });
  await p.keyboard.press('Tab');
  const wrapFwd = await active(p);
  ok('Tab من آخر عنصر ظاهر (عنوان القسم المطوي) يعود لأول عنصر (عنوان القسم الأول)', wrapFwd.inside && wrapFwd.visible && (await p.evaluate(() => document.activeElement === document.querySelector('#sidebar .nav-section'))));
  await p.keyboard.press('Shift+Tab');
  const wrapBack = await p.evaluate(() => document.activeElement === [...document.querySelectorAll('#sidebar .nav-section')].pop());
  ok('Shift+Tab من الأول يعود لآخر عنصر ظاهر', wrapBack);
  // إعادة فتح القسم: الروابط تعود ضمن الدوران
  await p.evaluate(() => [...document.querySelectorAll('#sidebar .nav-section')].pop().click());
  const list2 = await p.evaluate(() => [...document.getElementById('sidebar').querySelectorAll('a[href], button')].filter((e) => e.getClientRects().length).length);
  ok('إعادة فتح القسم تعيد عناصره إلى الدوران', list2 > list, `${list} -> ${list2}`);
  await p.screenshot({ path: `${OUT}/sidebar-mobile-open.jpg`, type: 'jpeg', quality: 60 });
  ok('منع تمرير الخلفية أثناء الفتح', await p.evaluate(() => document.body.classList.contains('sidebar-locked')));
  await p.keyboard.press('Escape');
  await p.waitForTimeout(250);
  ok('Escape يغلق القائمة ويعيد التركيز للزر ويحرر التمرير', await p.evaluate(() => !document.getElementById('sidebar').classList.contains('mobile-open') && document.activeElement.id === 'sidebarToggle' && !document.body.classList.contains('sidebar-locked')));
  await p.click('#sidebarToggle'); await p.waitForTimeout(250); await p.mouse.click(20, 400); await p.waitForTimeout(250);
  ok('النقر خارج القائمة يغلقها', await p.evaluate(() => !document.getElementById('sidebar').classList.contains('mobile-open')));
  await ctx.close();
}

// ---------- شريط الأيقونات ----------
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/users');
  await p.evaluate(() => localStorage.removeItem('rowad-sidebar-rail'));
  await p.reload();
  await p.click('#sidebarToggle');
  await p.waitForTimeout(400);
  const rail = () => p.evaluate(() => ({ collapsed: document.getElementById('sidebar').classList.contains('collapsed'), w: Math.round(document.getElementById('sidebar').getBoundingClientRect().width), heads: [...document.querySelectorAll('#sidebar .nav-section')].every((h) => h.getAttribute('tabindex') === '-1' && h.getAttribute('aria-hidden') === 'true'), noHeads: [...document.querySelectorAll('#sidebar .nav-section')].every((h) => !h.hasAttribute('tabindex') && !h.hasAttribute('aria-hidden')) }));
  let r = await rail();
  ok('الشريط المصغّر: العرض 64 وعناوين الأقسام tabindex=-1 وaria-hidden', r.collapsed && r.w === 64 && r.heads, JSON.stringify(r));
  await p.evaluate(() => document.querySelector('#sidebar a[href]').focus());
  let onHead = 0, n = 0;
  for (let i = 0; i < 40; i++) { await p.keyboard.press('Tab'); const a = await active(p); if (a.cls.includes('nav-section')) onHead++; n++; }
  ok('Tab في الشريط لا يقع على عناوين الأقسام المخفية', onHead === 0, `${n} ضغطة`);
  await p.focus('#sidebar .nav-link[href$="/admin/centers"]'); await p.keyboard.press('Enter'); await p.waitForURL('**/admin/centers');
  ok('التنقل بلوحة المفاتيح من الشريط', p.url().endsWith('/admin/centers'));
  await p.screenshot({ path: `${OUT}/sidebar-rail-desktop.jpg`, type: 'jpeg', quality: 60 });
  await p.setViewportSize({ width: 390, height: 844 }); await p.waitForTimeout(300);
  r = await rail();
  ok('الانتقال للجوال يعيد عناوين الأقسام لسلوكها الطبيعي', r.noHeads, JSON.stringify(r));
  await p.setViewportSize({ width: 1440, height: 900 }); await p.waitForTimeout(300);
  r = await rail();
  ok('العودة لسطح المكتب تستعيد الشريط المحفوظ', r.collapsed && r.heads);
  await p.click('#sidebarToggle'); await p.waitForTimeout(450);
  r = await rail();
  ok('التوسيع يعيد سلوك عناوين الأقسام (Tab وقارئ الشاشة)', !r.collapsed && r.noHeads && r.w === 260, JSON.stringify(r));
  await ctx.close();
}

// ---------- لوحة مدير المشاريع ----------
async function pm(email, shot) {
  const { ctx, p } = await login(email);
  await p.setViewportSize({ width: 1440, height: 900 });
  const resp = await p.goto(BASE + '/admin/projects-manager');
  const st = resp.status();
  const info = st === 200 ? await p.evaluate(() => ({ attn: !!document.getElementById('attn-title'), rows: document.querySelectorAll('#attn-title ~ .attention-card tbody tr').length, pill: document.querySelector('#attn-title .count-pill')?.textContent.trim(), general: document.body.textContent.includes('قوائم قيد المراجعة (عامة)') })) : null;
  if (shot && st === 200) for (const [n, w, h, t] of [['desktop-light', 1440, 900, 'light'], ['mobile-light', 390, 844, 'light'], ['desktop-dark', 1440, 900, 'dark']]) {
    await p.setViewportSize({ width: w, height: h });
    await p.evaluate((th) => { document.documentElement.dataset.theme = th; document.documentElement.dataset.bsTheme = th; }, t);
    await p.screenshot({ path: `${OUT}/${shot}-${n}.jpg`, fullPage: true, type: 'jpeg', quality: 60 });
  }
  await ctx.close();
  return { st, info };
}
{
  const a = await pm('pma@test.local', 'projects-manager-sign-only');
  ok('مدير مشاريع بطلبات توقيع فقط: قسم الأولويات بعدد 3 و3 صفوف وبلا قوائم عامة', a.st === 200 && a.info.attn && a.info.rows === 3 && a.info.pill === '3' && !a.info.general, JSON.stringify(a));
  const b = await pm('pmb@test.local', 'projects-manager-no-actions');
  ok('مدير مشاريع بلا إجراءات: لا قسم أولويات', b.st === 200 && !b.info.attn, JSON.stringify(b));
  const c = await pm('limited@test.local');
  ok('حساب محدود: 403 على لوحة مدير المشاريع', c.st === 403, 'status=' + c.st);
}

// ---------- نموذج الطالب (طويل) ----------
{
  const { ctx, p } = await login('admin@test.local');
  for (const [vn, w, h] of [['desktop', 1440, 900], ['tablet', 768, 1024], ['mobile', 390, 844]]) {
    for (const theme of ['light', 'dark']) {
      await p.setViewportSize({ width: w, height: h });
      await p.goto(BASE + '/admin/students/create');
      await p.evaluate((t) => { document.documentElement.dataset.theme = t; document.documentElement.dataset.bsTheme = t; }, theme);
      await p.waitForTimeout(150);
      const hs = await p.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
      ok(`نموذج الطالب ${vn}/${theme}: لا تمرير أفقي`, hs === 0, 'hscroll=' + hs);
      await p.screenshot({ path: `${OUT}/student-form-${vn}-${theme}.jpg`, fullPage: true, type: 'jpeg', quality: 60 });
      // فشل التحقق من الخادم (نعطّل تحقق HTML فقط ليصل الطلب إلى قواعد Laravel)
      await p.evaluate(() => { const f = document.querySelector('form[action$="/admin/students"]'); f.noValidate = true; f.querySelector('[name=student_code]').value = 'S-E2E'; f.querySelector('[name=first_name_ar]').value = 'ليلى'; f.querySelector('[name=last_name_ar]').value = ''; f.querySelector('[name=notes]').value = 'ملاحظة محفوظة'; f.querySelector('[name=gender]').value = 'female'; });
      await Promise.all([p.waitForNavigation(), p.click('form[action$="/admin/students"] button[type=submit]')]);
      await p.evaluate((t) => { document.documentElement.dataset.theme = t; document.documentElement.dataset.bsTheme = t; }, theme);
      await p.waitForTimeout(200);
      const st = await p.evaluate(() => ({ code: document.querySelector('[name=student_code]').value, notes: document.querySelector('[name=notes]').value, invalid: !!document.querySelector('.is-invalid'), fb: document.querySelector('.invalid-feedback.d-block')?.offsetHeight > 0, focus: document.activeElement.name }));
      ok(`فشل التحقق ${vn}/${theme}: المدخلات محفوظة والخطأ ظاهر والتركيز على الحقل`, st.code === 'S-E2E' && st.notes === 'ملاحظة محفوظة' && st.invalid && st.fb && st.focus === 'last_name_ar', JSON.stringify(st));
      await p.screenshot({ path: `${OUT}/student-form-error-${vn}-${theme}.jpg`, fullPage: true, type: 'jpeg', quality: 60 });
    }
  }
  await ctx.close();
}

await browser.close();
console.log(results.join('\n'));
process.exit(results.some((r) => r.startsWith('FAIL')) ? 1 : 0);
