{{--
    سكربت مشترك لأزرار محرر الأقسام (block-edit): إضافة/حذف صف في الجداول وإضافة/حذف بند في القوائم.
    يُستخدم في تعبئة وثائق المشاريع والتقرير الشهري — البحث عن الجدول/القائمة يكون ضمن .card-body الخاص بالقسم،
    مع رجوع للمستند كله إذا لم يوجد، وبدون رمي أخطاء يوقف بقية المعالجات.
--}}
<script>
document.addEventListener('click', function (e) {
    const removeRow = e.target.closest('.table-edit-remove');
    if (removeRow) {
        const tr = removeRow.closest('.table-edit-row');
        if (tr) tr.remove();
        return;
    }

    const removeItem = e.target.closest('.list-edit-remove');
    if (removeItem) {
        const item = removeItem.closest('.list-edit-item');
        if (item) item.remove();
        return;
    }

    const addRow = e.target.closest('.table-edit-add');
    if (addRow) {
        const scope = addRow.closest('.card-body') || document;
        const tbody = scope.querySelector('.table-edit-rows');
        if (!tbody) return;
        const cols = Math.max(1, parseInt(addRow.dataset.cols, 10) || 0);
        const inputName = 'blocks[' + addRow.dataset.key + '][rows][]';
        let html = '<tr class="table-edit-row">';
        for (let c = 0; c < cols; c++) {
            html += '<td><input type="text" name="' + inputName + '[' + c + ']" class="form-control form-control-sm"></td>';
        }
        html += '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger table-edit-remove"><i class="bi bi-x-lg"></i></button></td></tr>';
        tbody.insertAdjacentHTML('beforeend', html);
        return;
    }

    const addItem = e.target.closest('.list-edit-add');
    if (addItem) {
        const scope = addItem.closest('.card-body') || document;
        const container = scope.querySelector('.list-edit-items');
        if (!container) return;
        const html = '<div class="input-group mb-1 list-edit-item">' +
            '<input type="text" name="blocks[' + addItem.dataset.key + '][items][]" class="form-control form-control-sm">' +
            '<button type="button" class="btn btn-outline-danger btn-sm list-edit-remove"><i class="bi bi-x-lg"></i></button></div>';
        container.insertAdjacentHTML('beforeend', html);
    }
});
</script>
