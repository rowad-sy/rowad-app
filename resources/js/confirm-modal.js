/*
 * نافذة تأكيد منسّقة بدل confirm() الأصلي: تعترض النماذج/الأزرار التي تستخدم onsubmit/onclick="return confirm('...')"
 * وتعرض نافذة Bootstrap متحركة بنفس الرسالة. عند التأكيد يُنفَّذ الإجراء الأصلي كما هو (إرسال النموذج/النقر)، وعند الإلغاء لا يحدث شيء.
 * عرض فقط: لا تغيير في الرسائل ولا في الطلبات المرسلة.
 */
import * as bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

const RE = /^\s*return\s+confirm\(\s*(['"])([\s\S]*?)\1\s*\)\s*;?\s*$/;
let modalEl, modal, pending = null;

function ensure() {
    if (modalEl) return;
    modalEl = document.createElement('div');
    modalEl.className = 'modal fade confirm-modal';
    modalEl.tabIndex = -1;
    modalEl.setAttribute('aria-labelledby', 'confirmModalMsg');
    modalEl.setAttribute('aria-modal', 'true');
    modalEl.innerHTML = `
      <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-body text-center">
          <div class="confirm-icon" aria-hidden="true"><i class="bi bi-exclamation-triangle"></i></div>
          <p class="confirm-msg" id="confirmModalMsg"></p>
          <div class="d-flex gap-2 justify-content-center">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="button" class="btn btn-primary confirm-ok">تأكيد</button>
          </div>
        </div></div></div>`;
    document.body.appendChild(modalEl);
    modal = new bootstrap.Modal(modalEl);
    modalEl.querySelector('.confirm-ok').addEventListener('click', () => {
        const run = pending; pending = null; modal.hide(); if (run) run();
    });
    modalEl.addEventListener('hidden.bs.modal', () => { pending = null; });
    modalEl.addEventListener('shown.bs.modal', () => modalEl.querySelector('.confirm-ok').focus());
}

function ask(message, onOk) {
    ensure();
    const danger = /حذف|إلغاء|رفض|أرشفة|تعطيل|إزالة/.test(message);
    modalEl.classList.toggle('is-danger', danger);
    modalEl.querySelector('.confirm-icon i').className = 'bi ' + (danger ? 'bi-trash3' : 'bi-question-circle');
    const ok = modalEl.querySelector('.confirm-ok');
    ok.className = 'btn confirm-ok ' + (danger ? 'btn-danger' : 'btn-primary');
    modalEl.querySelector('.confirm-msg').textContent = message;
    pending = onOk;
    modal.show();
}

function without(el, attr, fn) {
    const v = el.getAttribute(attr);
    el.removeAttribute(attr);
    try { fn(); } finally { el.setAttribute(attr, v); }
}

document.addEventListener('submit', (e) => {
    const form = e.target;
    if (!(form instanceof HTMLFormElement)) return;
    const m = RE.exec(form.getAttribute('onsubmit') || '');
    if (!m) return;
    e.preventDefault(); e.stopImmediatePropagation();
    const submitter = e.submitter || null;
    ask(m[2], () => without(form, 'onsubmit', () => form.requestSubmit(submitter)));
}, true);

document.addEventListener('click', (e) => {
    const el = e.target.closest('[onclick]');
    if (!el) return;
    const m = RE.exec(el.getAttribute('onclick') || '');
    if (!m) return;
    e.preventDefault(); e.stopImmediatePropagation();
    ask(m[2], () => without(el, 'onclick', () => el.click()));
}, true);
