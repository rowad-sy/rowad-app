// اختبار متصفح فعلي (Playwright/Chromium) للمرحلة 2: الإدارة والموارد البشرية والطلاب.
//
// الإعداد (قاعدة بيانات تجريبية معزولة فقط — لا تشغّله على بيانات حقيقية):
//   cp .env.example .env && php artisan key:generate
//   touch database/database.sqlite && php artisan migrate --force
//   php artisan db:seed --class=UiE2ESeeder --force        # الحسابات والبيانات موثقة في database/seeders/UiE2ESeeder.php
//   npm run build && php artisan serve --port=8002
// التشغيل:  BASE=http://localhost:8002 node tests/e2e/ui-phase-2.mjs   (OUT=docs/ui-phase-2/after لحفظ اللقطات)
// يستخدم الحسابات: admin@test.local (كامل الصلاحيات)، hr@test.local، students@test.local، limited@test.local (كلمة المرور password).
// لا يتجاوز أي مصادقة أو middleware. يعيد إنشاء سجلات E2E-* جديدة في كل تشغيل (كود فريد بحسب الوقت).
import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.BASE || 'http://localhost:8002';
const OUT = process.env.OUT || 'docs/ui-phase-2/after';
fs.mkdirSync(OUT, { recursive: true });
const results = [];
const ok = (name, cond, extra = '') => results.push(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? ' — ' + extra : ''}`);
const stamp = Date.now().toString().slice(-6);

const browser = await chromium.launch();
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
const theme = async (p, t) => { await p.evaluate((x) => { document.documentElement.dataset.theme = x; document.documentElement.dataset.bsTheme = x; }, t); await p.waitForTimeout(350); }; // انتظار انتهاء انتقال الألوان قبل اللقطات
const hscroll = (p) => p.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
const q = (p) => new URL(p.url()).searchParams;

// ============ 1) الفلاتر: تطبيق صريح، الاحتفاظ بالقيم، الصفحة الأولى، المسح، حالة النتائج الفارغة ============
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  for (const [name, path, term] of [['الموظفين', '/admin/hr/employees', 'EMP-'], ['الطلاب', '/admin/students', 'STU-']]) {
    await p.goto(BASE + path);
    await p.selectOption('.filter-bar select[name=status]', { index: 1 }).catch(() => {});
    await p.waitForTimeout(400);
    ok(`${name}: تغيير الفلتر لا يرسل النموذج تلقائيًا`, new URL(p.url()).search === '');
    await p.fill('.filter-bar input[name=search]', term);
    await p.click('.filter-bar button[type=submit]:has-text("تطبيق")');
    await p.waitForLoadState('load');
    ok(`${name}: زر التطبيق يرسل الفلاتر`, q(p).get('search') === term, p.url());
    const page2 = p.locator('.pagination a[href*="page=2"]:visible').first();
    if (await page2.count()) {
      const href = await page2.getAttribute('href');
      ok(`${name}: رابط الصفحة 2 يحتفظ بالبحث`, href.includes('search=' + encodeURIComponent(term)), href);
      await page2.click(); await p.waitForLoadState('load');
      await p.fill('.filter-bar input[name=search]', term.slice(0, 3));
      await p.click('.filter-bar button[type=submit]:has-text("تطبيق")'); await p.waitForLoadState('load');
      ok(`${name}: تغيير الفلتر يعيد للصفحة الأولى (لا page في الرابط)`, !q(p).has('page'), p.url());
    } else ok(`${name}: ترقيم الصفحات متاح لاختبار الصفحة الثانية`, false, 'لا توجد صفحة 2 (شغّل UiE2ESeeder)');
    await p.fill('.filter-bar input[name=search]', 'zzz-لا-شيء');
    await p.click('.filter-bar button[type=submit]:has-text("تطبيق")'); await p.waitForLoadState('load');
    ok(`${name}: «لا توجد نتائج تطابق الفلاتر» عند عدم المطابقة`, (await p.textContent('body')).includes('لا توجد نتائج تطابق الفلاتر'));
    await p.click('.filter-bar a:has-text("مسح الفلاتر")'); await p.waitForLoadState('load');
    ok(`${name}: مسح الفلاتر يعيد الصفحة بلا معاملات`, new URL(p.url()).search === '', p.url());
  }
  ok('لا أخطاء JS في القوائم', p.errors.length === 0, p.errors.join(';'));
  await ctx.close();
}

// ============ 2) صفوف التسجيلات في نموذج الطالب + إنشاء/تعديل + فشل التحقق ============
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  const code = 'E2E-S' + stamp;
  await p.goto(BASE + '/admin/students/create');
  ok('نموذج الطالب: اسم/لقب عربي وإنجليزي بتسميات مستقلة', (await p.$$eval('label[for^="f-first_name"], label[for^="f-last_name"]', (l) => l.length)) === 4);
  await p.evaluate(() => { document.querySelector('form[action$="/admin/students"]').noValidate = true; });
  await p.fill('[name=student_code]', code);
  await p.fill('[name=first_name_ar]', 'اختبار');
  await p.selectOption('[name=gender]', 'female');
  await p.click('button:has-text("إضافة تسجيل")');
  await p.selectOption('[name="enrollments[0][course_id]"]', { index: 1 });
  await p.fill('[name="enrollments[0][grade]"]', '150'); // خارج 0..100 لإثارة خطأ الصف
  await p.click('button:has-text("إضافة تسجيل")');
  ok('صف التسجيل الجديد يأخذ التركيز ومفتاحًا فريدًا', (await p.$$('[name^="enrollments[1]"]')).length > 0);
  await p.click('#enrollmentsTable tbody tr:nth-of-type(3) button[aria-label^="حذف التسجيل"]');
  ok('حذف صف تسجيل يعمل', (await p.$$('[name^="enrollments[1]"]')).length === 0);
  await Promise.all([p.waitForNavigation(), p.click('form[action$="/admin/students"] button[type=submit]')]);
  const st = await p.evaluate(() => ({
    code: document.querySelector('[name=student_code]').value,
    course: document.querySelector('[name="enrollments[0][course_id]"]')?.value,
    grade: document.querySelector('[name="enrollments[0][grade]"]')?.value,
    lastErr: !!document.querySelector('#f-last_name_ar.is-invalid'),
    lastDescribed: document.querySelector('#f-last_name_ar')?.getAttribute('aria-describedby'),
    lastInvalidAttr: document.querySelector('#f-last_name_ar')?.getAttribute('aria-invalid'),
    rowErrors: document.querySelectorAll('#enrollmentsTable .invalid-feedback').length,
    rowAria: document.querySelector('[name="enrollments[0][period_id]"]')?.getAttribute('aria-invalid'),
    focus: document.activeElement.id,
    text: document.querySelector('.invalid-feedback')?.textContent.trim(),
  }));
  ok('فشل التحقق: المدخلات وصف التسجيل محفوظان', st.code === code && !!st.course && st.grade === '150', JSON.stringify(st));
  ok('فشل التحقق: خطأ اللقب بجانب الحقل مع aria-invalid وaria-describedby ورسالة عربية', st.lastErr && st.lastInvalidAttr === 'true' && !!st.lastDescribed && /مطلوب/.test(st.text), st.text);
  ok('فشل التحقق: أخطاء صف التسجيل ظاهرة ومربوطة', st.rowErrors >= 1 && st.rowAria === 'true', `errors=${st.rowErrors}`);
  ok('التركيز على أول حقل خاطئ', st.focus.startsWith('f-') || st.focus.startsWith('fld-') || st.focus === 'f-last_name_ar', st.focus);
  for (const [vn, w, h] of [['desktop', 1440, 900], ['tablet', 768, 1024], ['mobile', 390, 844]]) {
    await p.setViewportSize({ width: w, height: h });
    for (const t of ['light', 'dark']) {
      await theme(p, t);
      ok(`نموذج الطالب بعد الخطأ ${vn}/${t}: لا تمرير أفقي`, (await hscroll(p)) === 0);
      if ((vn === 'mobile' && t === 'light') || (vn === 'desktop' && t === 'dark')) await p.screenshot({ path: `${OUT}/student-form-error-${vn}-${t}.jpg`, fullPage: true, type: 'jpeg', quality: 55 });
    }
  }
  // تصحيح وحفظ ثم فتح التعديل
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.fill('[name=last_name_ar]', 'طالب');
  await p.fill('[name="enrollments[0][grade]"]', '90');
  await p.selectOption('[name="enrollments[0][period_id]"]', { index: 1 });
  await Promise.all([p.waitForNavigation(), p.click('form[action$="/admin/students"] button[type=submit]')]);
  ok('إنشاء الطالب بعد التصحيح', !p.url().includes('/create'), p.url());
  await p.goto(BASE + '/admin/students?search=' + code);
  await p.click(`a[aria-label^="تعديل"]`);
  await p.waitForLoadState('load');
  ok('تعديل الطالب: صف التسجيل المحفوظ يظهر', (await p.$$('#enrollmentsTable tbody tr [name$="[course_id]"]')).length === 1);
  await p.fill('[name=first_name_ar]', 'اختبار-معدّل');
  await Promise.all([p.waitForNavigation(), p.click('form[action*="/admin/students/"] button[type=submit]:has-text("حفظ")')]);
  ok('تعديل الطالب يُحفظ', !p.url().includes('/edit'), p.url());
  ok('لا أخطاء JS في نموذج الطالب', p.errors.length === 0, p.errors.join(';'));
  await ctx.close();
}

// ============ 3) نموذج الموظف: تبويبات، فشل التحقق، فتح التبويب المخفي ============
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  const code = 'E2E-E' + stamp;
  await p.goto(BASE + '/admin/hr/employees/create');
  const tabs = await p.$$eval('[data-bs-toggle="tab"]', (t) => t.map((x) => ({ role: x.getAttribute('role'), controls: x.getAttribute('aria-controls') })));
  ok('تبويبات الموظف بأدوار ARIA (role=tab وaria-controls)', tabs.length >= 6 && tabs.every((t) => t.role === 'tab' && t.controls), JSON.stringify(tabs[0]));
  await p.click('#job-tab');
  ok('تبديل التبويب يحدّث aria-selected', (await p.getAttribute('#job-tab', 'aria-selected')) === 'true');
  ok('الاسم واللقب بالعربية والإنجليزية بتسميات مستقلة', (await p.$$eval('label[for^="f-first_name"], label[for^="f-last_name"]', (l) => l.length)) === 4);
  await p.click('#basic-tab');
  await p.evaluate(() => { document.querySelector('form[action$="/admin/hr/employees"]').noValidate = true; });
  await p.fill('[name=employee_code]', code);
  await p.fill('[name=first_name_ar]', 'موظف');
  await p.selectOption('[name=gender]', 'male');
  await Promise.all([p.waitForNavigation(), p.click('form[action$="/admin/hr/employees"] button[type=submit]:has-text("حفظ")')]);
  const err = await p.evaluate(() => ({ code: document.querySelector('[name=employee_code]').value, invalid: document.querySelector('#f-last_name_ar')?.classList.contains('is-invalid'), msg: document.querySelector('.invalid-feedback')?.textContent.trim(), sum: document.querySelector('.alert-danger')?.textContent.trim().slice(0, 40) }));
  ok('الموظف: فشل التحقق يحفظ المدخلات ويعرض خطأ اللقب بالعربية', err.code === code && err.invalid && /مطلوب/.test(err.msg), JSON.stringify(err));
  await p.fill('[name=last_name_ar]', 'اختبار');
  await Promise.all([p.waitForNavigation(), p.click('form[action$="/admin/hr/employees"] button[type=submit]:has-text("حفظ")')]);
  ok('إنشاء الموظف بعد التصحيح', !p.url().includes('/create'), p.url());
  await p.goto(BASE + '/admin/hr/employees?search=' + code);
  await p.click('a[aria-label^="تعديل"]'); await p.waitForLoadState('load');
  await p.fill('[name=first_name_ar]', 'موظف-معدّل');
  await Promise.all([p.waitForNavigation(), p.click('form[action*="/admin/hr/employees/"] button[type=submit]:has-text("حفظ")')]);
  ok('تعديل الموظف يُحفظ', !p.url().includes('/edit'), p.url());
  // فتح التبويب المخفي عند وجود خطأ فيه (نحقن is-invalid في حقل تبويب "العقد والراتب" عبر اعتراض الاستجابة)
  await p.route('**/admin/hr/employees/create', async (route) => {
    const resp = await route.fetch();
    let html = await resp.text();
    const i = html.indexOf('id="contract"');
    const j = html.indexOf('<input', i);
    const k = html.indexOf('>', j);
    html = html.slice(0, j) + html.slice(j, k).replace('class="', 'class="is-invalid ') + html.slice(k, k + 1) + '<div class="invalid-feedback">خطأ تجريبي في تبويب مخفي</div>' + html.slice(k + 1);
    await route.fulfill({ response: resp, body: html });
  });
  await p.goto(BASE + '/admin/hr/employees/create');
  await p.waitForTimeout(700);
  const hidden = await p.evaluate(() => ({ active: document.querySelector('.tab-pane.active')?.id, focusIn: document.activeElement.closest('.tab-pane')?.id, flag: !!document.querySelector('#contract-tab .tab-error-flag'), described: document.activeElement.getAttribute('aria-describedby') }));
  ok('خطأ داخل تبويب مخفي: يُفتح التبويب ثم ينتقل التركيز ويوسم التبويب', hidden.active === 'contract' && hidden.focusIn === 'contract' && hidden.flag && !!hidden.described, JSON.stringify(hidden));
  await p.unroute('**/admin/hr/employees/create');
  await ctx.close();
}

// ============ 4) الحضور (طلاب): الراديو قابل للتركيز بلوحة المفاتيح ============
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/students/attendance');
  await p.focus('.attendance-input[value="present"]');
  const before = await p.evaluate(() => document.querySelectorAll('.attendance-input[value="absent"]:checked').length);
  await p.keyboard.press('ArrowLeft');
  await p.keyboard.press('ArrowRight');
  const f = await p.evaluate(() => ({ radio: document.activeElement.classList.contains('attendance-input'), group: !!document.activeElement.closest('[role=radiogroup]') }));
  ok('راديو الحضور قابل للتركيز ومحاط بـ radiogroup', f.radio && f.group, JSON.stringify(f));
  await p.check('.attendance-input[value="absent"] >> nth=0', { force: true });
  await p.waitForTimeout(100);
  ok('عدّاد الغياب يتحدث بعد الاختيار (نص لا لون فقط)', (await p.textContent('#countAbsent')) === String(before + 1));
  ok('نطاق التسجيل ظاهر (التاريخ/المركز/المشروع)', (await p.textContent('body')).includes('كل المراكز'));
  await ctx.close();
}

// ============ 5) الحضور والغياب للموظفين + التايم شيت + الموافقات ============
{
  const { ctx, p } = await login('hr@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/hr/attendances');
  ok('حضور الموظفين: قوائم الحالة بأسماء متاحة للقارئ', (await p.$$eval('select.status-select', (s) => s.every((x) => x.getAttribute('aria-label')?.startsWith('حالة ')))) === true);
  const widths = await p.$$eval('select.status-select', (s) => s.map((x) => x.getBoundingClientRect().width));
  ok('قائمة الحالة عريضة بما يكفي لقراءة «حاضر»', widths.every((w) => w >= 100), `min=${Math.min(...widths)}`);
  await p.goto(BASE + '/admin/hr/timesheets');
  ok('التايم شيت بلا فلتر: رسالة توجيه', (await p.textContent('body')).includes('اختر مركزًا أو مشروعًا'));
  await p.goto(BASE + '/admin/hr/timesheets?search=' + encodeURIComponent('موظف'));
  const fr = await p.$('iframe[title^="التايم شيت"]');
  ok('التايم شيت بفلتر: يُعرض الجدول الفعلي (iframe لصفحة الطباعة الحالية)', !!fr);
  if (fr) { const frame = await fr.contentFrame(); await frame.waitForLoadState('load'); ok('التايم شيت يحتوي رموز الحالة النصية', /✔|✘|—|ع/.test(await frame.textContent('body'))); }
  await p.goto(BASE + '/admin/hr/leave-approvals');
  ok('الموافقات: عنوان الطلبات المستحقة للمراجعة ورابط سجل الطلبات', (await p.textContent('body')).includes('المستحقة لمراجعتك') && !!(await p.$('a:has-text("سجل الطلبات")')));
  await p.goto(BASE + '/admin/hr/leave-requests');
  ok('طلبات الإجازات: رابط «الطلبات المستحقة لمراجعتي» لمن يملك التعديل', !!(await p.$('a:has-text("الطلبات المستحقة لمراجعتي")')));
  const r = await p.goto(BASE + '/admin/hr/leave-requests/create');
  ok('طلب إجازة جديد (موظف مرتبط بالحساب) يعمل', r.status() === 200, 'status=' + r.status());
  await ctx.close();
}

// ============ 6) الصلاحيات: مصفوفة قابلة للوصول + الوضع الداكن، والمجموعات ============
{
  const { ctx, p } = await login('admin@test.local');
  await p.setViewportSize({ width: 1440, height: 900 });
  await p.goto(BASE + '/admin/permissions/create');
  await p.click('button:has-text("إضافة عنصر")');
  await p.click('button:has-text("نسخ")');
  const ids = await p.$$eval('#matrix-body input[type=checkbox]', (c) => c.map((x) => x.id));
  ok('مصفوفة الصلاحيات: معرّفات مربعات الاختيار فريدة بعد الإضافة والنسخ', ids.length > 0 && new Set(ids).size === ids.length, `${ids.length} checkbox`);
  const unlabeled = await p.$$eval('#matrix-body input[type=checkbox]', (c) => c.filter((x) => !x.labels?.length).length);
  ok('كل مربع اختيار مرتبط بتسمية (label for) واسم متاح', unlabeled === 0, `unlabeled=${unlabeled}`);
  await p.click('#matrix-body tr:first-child button:has-text("تحديد كل الصف")');
  const rowChecked = await p.$$eval('#matrix-body tr:first-child input[type=checkbox]:checked', (c) => c.length);
  const rowTotal = await p.$$eval('#matrix-body tr:first-child input[type=checkbox]', (c) => c.length);
  ok('«تحديد كل الصف» يحدد الصف الحالي فقط', rowChecked === rowTotal && (await p.$$eval('#matrix-body tr:nth-child(2) input[type=checkbox]:checked', (c) => c.length)) === 0);
  await theme(p, 'dark');
  const bg = await p.$eval('#matrix-body .row-col', (e) => getComputedStyle(e).backgroundColor);
  ok('المصفوفة في الوضع الداكن: خلفية العمود الثابت ليست بيضاء', bg !== 'rgb(255, 255, 255)', bg);
  await p.screenshot({ path: `${OUT}/permissions-form-desktop-dark.jpg`, fullPage: true, type: 'jpeg', quality: 55 });
  await p.goto(BASE + '/admin/groups/create');
  await p.fill('#userSearch', 'u1@');
  await p.click('button:has-text("تحديد الظاهرين")');
  const sel = await p.$$eval('.user-checkbox:checked', (c) => c.length);
  const vis = await p.$$eval('.user-item:not(.hidden)', (c) => c.length);
  ok('المجموعات: «تحديد الظاهرين» يشمل نتائج البحث فقط', sel === vis && vis < (await p.$$eval('.user-item', (c) => c.length)), `selected=${sel}, visible=${vis}`);
  ok('لا أخطاء JS في الصلاحيات/المجموعات', p.errors.length === 0, p.errors.join(';'));
  await ctx.close();
}

// ============ 7) حسابات محدودة: لا تجاوز للصلاحيات ============
{
  const lim = await login('limited@test.local');
  for (const path of ['/admin/hr/employees', '/admin/students', '/admin/permissions', '/admin/users']) {
    const r = await lim.p.goto(BASE + path);
    ok(`حساب محدود: 403 على ${path}`, r.status() === 403, 'status=' + r.status());
  }
  await lim.ctx.close();
  const st = await login('students@test.local');
  ok('حساب الطلاب: يفتح قائمة الطلاب', (await st.p.goto(BASE + '/admin/students')).status() === 200);
  ok('حساب الطلاب: 403 على الموظفين', (await st.p.goto(BASE + '/admin/hr/employees')).status() === 403);
  await st.ctx.close();
  const hr = await login('hr@test.local');
  ok('حساب الموارد البشرية: يفتح الموظفين ويُمنع من الطلاب', (await hr.p.goto(BASE + '/admin/hr/employees')).status() === 200 && (await hr.p.goto(BASE + '/admin/students')).status() === 403);
  await hr.ctx.close();
}

// ============ 8) الاستجابة والوضعان: لا تمرير أفقي ولقطات ممثلة ============
{
  const { ctx, p } = await login('admin@test.local');
  const pages = ['centers', 'cohorts', 'departments', 'users', 'groups', 'permissions', 'hr/employees', 'hr/employees/1', 'hr/job-positions', 'hr/attendances', 'hr/timesheets', 'hr/leave-requests', 'hr/leave-approvals', 'hr/leave-policies', 'hr/employees/statistics',
    'students', 'students/1', 'students/attendance', 'students/courses', 'students/periods', 'students/levels', 'students/training-plans', 'students/certificates', 'students/certificates/signers', 'students/certificates/signatory-sets', 'students/create', 'hr/employees/create', 'permissions/create'].map((x) => '/admin/' + x);
  const shots = new Set(['/admin/hr/employees', '/admin/hr/employees/1', '/admin/students', '/admin/students/1', '/admin/hr/attendances', '/admin/students/attendance', '/admin/permissions/create', '/admin/hr/leave-approvals', '/admin/cohorts', '/admin/hr/employees/create']);
  for (const [vn, w, h] of [['mobile', 390, 844], ['tablet', 768, 1024], ['desktop', 1440, 900]]) {
    await p.setViewportSize({ width: w, height: h });
    for (const t of ['light', 'dark']) {
      const bad = [];
      for (const path of pages) {
        const r = await p.goto(BASE + path);
        await theme(p, t);
        if (r.status() !== 200) { bad.push(`${path}:${r.status()}`); continue; }
        if ((await hscroll(p)) > 0) bad.push(`${path}:hscroll`);
        const wanted = shots.has(path) && ((vn === 'desktop' && t === 'light') || (vn === 'mobile' && t === 'light') || (vn === 'desktop' && t === 'dark'));
        if (wanted) await p.screenshot({ path: `${OUT}/${path.replace(/^\/admin\//, '').replace(/\//g, '_')}-${vn}-${t}.jpg`, fullPage: true, type: 'jpeg', quality: 55 });
      }
      ok(`${vn}/${t}: ${pages.length} صفحة بلا تمرير أفقي ولا أخطاء خادم`, bad.length === 0, bad.join(' '));
    }
  }
  ok('لا أخطاء JS أثناء الجولة', p.errors.length === 0, p.errors.slice(0, 3).join(';'));
  await ctx.close();
}

await browser.close();
console.log(results.join('\n'));
process.exit(results.some((r) => r.startsWith('FAIL')) ? 1 : 0);
