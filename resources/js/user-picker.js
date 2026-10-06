/*
 * قائمة مستخدمين منسدلة مع بحث: ترقية <select class="user-picker"> الأصلية.
 * يبقى الـ select مصدر القيمة (name/value) لكنه يُخفى، ويظهر مكانه حقل بحث
 * وقائمة منسدلة. تُزال required من select — التحقق يتم في الخادم.
 */
(function () {
    function normalize(s) {
        return (s || '').replace(/\u0640/g, '').replace(/[إأآا]/g, 'ا').replace(/ى/g, 'ي').replace(/ة/g, 'ه').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function enhance(sel) {
        if (sel.dataset.pickerReady) return;
        sel.dataset.pickerReady = '1';

        const wrap = document.createElement('div');
        wrap.className = 'user-picker-wrap';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'form-control user-picker-input';
        input.placeholder = sel.dataset.placeholder || 'ابحث واختر مستخدمًا...';
        input.autocomplete = 'off';
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-expanded', 'false');
        wrap.insertBefore(input, sel);

        const icon = document.createElement('i');
        icon.className = 'bi bi-search user-picker-search';
        icon.setAttribute('aria-hidden', 'true');
        wrap.appendChild(icon);

        const menu = document.createElement('div');
        menu.className = 'user-picker-menu d-none';
        menu.setAttribute('role', 'listbox');
        wrap.appendChild(menu);

        const emptyHint = document.createElement('div');
        emptyHint.className = 'user-picker-empty d-none';
        emptyHint.textContent = 'لا نتائج مطابقة';

        const items = [];
        Array.from(sel.options).filter((o) => o.value !== '').forEach((o) => {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'user-picker-item';
            item.setAttribute('role', 'option');
            item.dataset.value = o.value;
            item.dataset.search = normalize(o.textContent);
            item.dataset.label = o.textContent.trim();
            item.textContent = o.textContent.trim();
            item.addEventListener('mousedown', (e) => {
                e.preventDefault();
                choose(item);
            });
            menu.appendChild(item);
            items.push(item);
        });
        menu.appendChild(emptyHint);

        sel.removeAttribute('required');
        sel.classList.add('d-none');

        let lastChosen = '';

        function syncFromSelect() {
            const chosen = sel.selectedOptions[0];
            input.value = chosen && chosen.value !== '' ? chosen.textContent.trim() : '';
            lastChosen = input.value;
        }

        function choose(item) {
            sel.value = item.dataset.value;
            sel.dispatchEvent(new Event('change', { bubbles: true }));
            syncFromSelect();
            close();
        }

        function filter(term) {
            const t = normalize(term);
            let visible = 0;
            items.forEach((el) => {
                const match = !t || el.dataset.search.includes(t) || el.dataset.value === term;
                el.classList.toggle('d-none', !match);
                if (match) visible++;
            });
            emptyHint.classList.toggle('d-none', visible > 0);
        }

        function open() {
            // عند الفتح والنص لم يُعدَّل بعد الاختيار: اعرض كل المستخدمين بدل تطابق اسم المختار وحده
            const typed = input.value !== lastChosen;
            filter(typed ? input.value : '');
            menu.classList.remove('d-none');
            input.setAttribute('aria-expanded', 'true');
        }

        function close() {
            menu.classList.add('d-none');
            input.setAttribute('aria-expanded', 'false');
        }

        input.addEventListener('focus', () => { open(); input.select(); });
        input.addEventListener('input', () => { open(); });
        input.addEventListener('click', () => { if (!menu.classList.contains('d-none')) filter(input.value !== lastChosen ? input.value : ''); });
        input.addEventListener('blur', () => setTimeout(close, 120));
        input.addEventListener('keydown', (e) => {
            const shown = items.filter((el) => !el.classList.contains('d-none'));
            const activeIdx = shown.findIndex((el) => el.classList.contains('is-active'));
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                if (!shown.length) return;
                const next = e.key === 'ArrowDown'
                    ? shown[Math.min(shown.length - 1, activeIdx + 1)]
                    : shown[Math.max(0, activeIdx - 1)];
                items.forEach((el) => el.classList.remove('is-active'));
                next.classList.add('is-active');
                next.scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                if (menu.classList.contains('d-none')) { open(); return; }
                const active = menu.querySelector('.is-active') || shown[0];
                if (active) { e.preventDefault(); choose(active); }
            } else if (e.key === 'Escape') {
                close();
            }
        });

        syncFromSelect();
    }

    document.querySelectorAll('select.user-picker').forEach(enhance);

    const mo = new MutationObserver((records) => {
        records.forEach((r) => r.addedNodes.forEach((n) => {
            if (n.querySelectorAll) n.querySelectorAll('select.user-picker').forEach(enhance);
        }));
    });
    mo.observe(document.body, { childList: true, subtree: true });
})();
