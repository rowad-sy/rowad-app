/*
 * جداول كبطاقات على الجوال: يضيف data-label لكل خلية من نص رأس العمود، ويتولى CSS عرضها كبطاقات تحت 576px.
 * عرض فقط: لا يغيّر محتوى الخلايا ولا البيانات. للاستثناء: أضف الصنف no-cards إلى الجدول.
 */
const MQ = '(max-width: 575.98px)';

function label(table) {
    if (table.classList.contains('no-cards') || table.dataset.cards === 'done') return;
    const heads = table.querySelectorAll('thead tr:last-child > th');
    if (!heads.length || table.querySelector('thead tr:nth-child(2)')) return; // رأس بسيط من صف واحد فقط
    const names = [...heads].map((h) => h.textContent.trim());
    table.querySelectorAll('tbody > tr').forEach((tr) => {
        const cells = [...tr.children];
        if (cells.some((c) => c.hasAttribute('colspan') || c.hasAttribute('rowspan'))) { tr.classList.add('card-span'); return; }
        cells.forEach((td, i) => { if (!td.dataset.label && names[i]) td.dataset.label = names[i]; });
    });
    table.classList.add('table-cards');
}

function run(root = document) {
    if (!window.matchMedia(MQ).matches) return;
    root.querySelectorAll('.table-responsive > table.table').forEach(label);
}

let t;
const schedule = () => { clearTimeout(t); t = setTimeout(run, 60); };
document.addEventListener('DOMContentLoaded', () => {
    run();
    // Livewire/Alpine قد يعيد رسم الجداول
    new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
    window.matchMedia(MQ).addEventListener('change', run);
});
