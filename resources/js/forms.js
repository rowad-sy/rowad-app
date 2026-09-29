/*
 * تحسينات وصول ونماذج مشتركة (المرحلة 2) — تعمل على أي نموذج في لوحة الإدارة دون تغيير أسماء الحقول أو الإرسال:
 *  1) ربط label بالحقل عند غياب for، وإعطاء الحقول الثانية في الصف تسمية من placeholder.
 *  2) علامة «مطلوب» للحقول required التي لا تحمل علامة.
 *  3) aria-invalid وaria-describedby للحقول الخاطئة وربطها برسالة الخطأ المجاورة.
 *  4) فتح التبويب/القسم المطوي الذي فيه أول خطأ ثم نقل التركيز إليه، ووسم التبويبات التي بها أخطاء.
 */
let uid = 0;
const CONTROL = 'input:not([type=hidden]):not([type=checkbox]):not([type=radio]):not([type=file]),select,textarea';

function slug(v) {
    return String(v || 'field').replace(/[^\w-]+/g, '_');
}

function ensureId(el, prefix) {
    if (!el.id) el.id = `${prefix}-${slug(el.name)}-${++uid}`;
    return el.id;
}

function linkLabels(root) {
    root.querySelectorAll('label.form-label:not([for])').forEach((label) => {
        const scope = label.parentElement;
        if (!scope) return;
        const control = scope.querySelector(CONTROL);
        if (!control || (control.labels && control.labels.length)) return;
        label.setAttribute('for', ensureId(control, 'fld'));
    });
    // حقل بلا تسمية (مثل اللقب بجانب الاسم): استخدم placeholder كاسم مُتاح
    root.querySelectorAll(CONTROL).forEach((control) => {
        if (control.closest('.filter-bar')) return;
        const labelled = (control.labels && control.labels.length) || control.hasAttribute('aria-label') || control.hasAttribute('aria-labelledby');
        if (!labelled && control.placeholder) control.setAttribute('aria-label', control.placeholder);
    });
}

function markRequired(root) {
    root.querySelectorAll('label.form-label').forEach((label) => {
        if (label.querySelector('.text-danger, .required-mark')) return;
        const id = label.getAttribute('for');
        const control = id ? document.getElementById(id) : null;
        if (control && control.required) {
            label.insertAdjacentHTML('beforeend', ' <span class="text-danger required-mark" aria-hidden="true">*</span><span class="visually-hidden"> (مطلوب)</span>');
        }
    });
}

function feedbackFor(control) {
    let n = control.nextElementSibling;
    while (n) {
        if (n.classList.contains('invalid-feedback')) return n;
        n = n.nextElementSibling;
    }
    const col = control.closest('[class*="col-"], .mb-3, td');
    return col ? col.querySelector('.invalid-feedback') : null;
}

function linkErrors(root) {
    const invalid = Array.from(root.querySelectorAll('.is-invalid')).filter((c) => c.matches(`${CONTROL}, input[type=checkbox], input[type=radio], input[type=file]`));
    invalid.forEach((control) => {
        control.setAttribute('aria-invalid', 'true');
        const fb = feedbackFor(control);
        if (!fb) return;
        if (!fb.id) fb.id = `err-${ensureId(control, 'fld')}`;
        const cur = (control.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (!cur.includes(fb.id)) cur.push(fb.id);
        control.setAttribute('aria-describedby', cur.join(' '));
    });
    return invalid;
}

function revealAndFocus(control) {
    // افتح كل قسم مطوي (details) وتبويب مخفي يحتوي الحقل قبل نقل التركيز
    let node = control;
    while ((node = node.closest('details:not([open])'))) node.open = true;
    const pane = control.closest('.tab-pane');
    const focus = () => {
        control.scrollIntoView({ block: 'center' });
        control.focus({ preventScroll: true });
    };
    if (pane && !pane.classList.contains('active') && window.bootstrap) {
        const trigger = document.querySelector(`[data-bs-target="#${pane.id}"], a[href="#${pane.id}"]`);
        if (trigger) {
            trigger.addEventListener('shown.bs.tab', focus, { once: true });
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
            return;
        }
    }
    focus();
}

function flagTabs(invalid) {
    const seen = new Set();
    invalid.forEach((c) => {
        const pane = c.closest('.tab-pane');
        if (!pane || seen.has(pane.id)) return;
        seen.add(pane.id);
        const trigger = document.querySelector(`[data-bs-target="#${pane.id}"], a[href="#${pane.id}"]`);
        if (trigger && !trigger.querySelector('.tab-error-flag')) {
            trigger.insertAdjacentHTML('beforeend', ' <span class="tab-error-flag badge text-bg-danger" title="يوجد خطأ في هذا التبويب">!<span class="visually-hidden"> يوجد خطأ في هذا التبويب</span></span>');
        }
    });
}

function tabSemantics() {
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach((t) => {
        if (!t.hasAttribute('role')) t.setAttribute('role', 'tab');
        const target = (t.getAttribute('data-bs-target') || t.getAttribute('href') || '').replace(/^#/, '');
        if (!target) return;
        if (!t.hasAttribute('aria-controls')) t.setAttribute('aria-controls', target);
        const pane = document.getElementById(target);
        if (pane && !pane.hasAttribute('aria-labelledby') && t.id) pane.setAttribute('aria-labelledby', t.id);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.page-content') || document.body;
    tabSemantics();
    linkLabels(root);
    markRequired(root);
    const invalid = linkErrors(root);
    if (invalid.length) {
        flagTabs(invalid);
        revealAndFocus(invalid[0]);
    }
});
