@extends('admin.layouts.master')

@section('title', isset($card) ? 'تعديل بطاقة فعالية' : 'بطاقة فعالية جديدة')

@php
    $attempted = session()->hasOldInput();
    $rows = function ($key) use ($card, $attempted) {
        // بعد محاولة حفظ فاشلة تُستعاد الصفوف المُدخلة فقط (حتى لو حُذفت كلها)؛ وعند أول فتح تُقرأ من السجل
        $old = $attempted ? old($key, []) : ($card?->{$key} ?? []);
        return collect(is_array($old) ? $old : [])->map(fn ($r) => (array) $r)->all();
    };
    $seed = [
        'content_items' => $rows('content_items'),
        'logistics_items' => $rows('logistics_items'),
        'purchases_items' => $rows('purchases_items'),
        'media_items' => $rows('media_items'),
        'transport_items' => $rows('transport_items'),
        'budget_items' => $rows('budget_items'),
    ];
@endphp

@section('content')
<x-page-header :title="isset($card) ? 'تعديل بطاقة فعالية' : 'بطاقة فعالية جديدة'"
               :breadcrumb="[['label' => 'بطاقات الفعاليات', 'url' => route('admin.event-cards.index')], ['label' => isset($card) ? $card->name : 'جديد']]" />

<form method="POST" action="{{ isset($card) ? route('admin.event-cards.update', $card) : route('admin.event-cards.store') }}">
    @csrf
    @if (isset($card)) @method('PUT') @endif

    {{-- معلومات الفعالية --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-danger me-1"></i> معلومات عن الفعالية</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">اسم الفعالية <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $card->name ?? '') }}" required>
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">اسم المشروع</label>
                <select name="project_id" class="form-select">
                    <option value="">— بدون —</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((int) old('project_id', $card->project_id ?? 0) === $project->id)>{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">التاريخ</label>
                <input type="date" name="event_date" class="form-control" value="{{ old('event_date', isset($card) && $card->event_date ? $card->event_date->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">المكان</label>
                <input type="text" name="location" class="form-control" value="{{ old('location', $card->location ?? '') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">المنظم</label>
                <input type="text" name="organizer" class="form-control" value="{{ old('organizer', $card->organizer ?? '') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">مقدم الحفل</label>
                <input type="text" name="presenter" class="form-control" value="{{ old('presenter', $card->presenter ?? '') }}">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">عدد الحضور المتوقع</label>
                <input type="number" min="0" name="expected_attendance" class="form-control" value="{{ old('expected_attendance', $card->expected_attendance ?? '') }}">
            </div>
            <div class="col-md-9 mb-3">
                <label class="form-label">أهداف الفعالية</label>
                <textarea name="objectives" rows="3" class="form-control">{{ old('objectives', $card->objectives ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- الجدول الزمني --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-clock-history text-danger me-1"></i> الجدول الزمني</h6>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">المكان</label>
                <input type="text" name="schedule_place" class="form-control" value="{{ old('schedule_place', $card->schedule_place ?? '') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">التاريخ</label>
                <input type="date" name="schedule_date" class="form-control" value="{{ old('schedule_date', isset($card) && $card->schedule_date ? $card->schedule_date->format('Y-m-d') : '') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">الساعة</label>
                <input type="time" name="schedule_time" class="form-control" value="{{ old('schedule_time', $card->schedule_time ?? '') }}">
            </div>
        </div>
    </div>

    {{-- تقسيم المهام --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-people text-danger me-1"></i> تقسيم المهام على الفريق</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">إدارة المشاريع</label>
                <textarea name="tasks_projects" rows="3" class="form-control">{{ old('tasks_projects', $card->tasks_projects ?? '') }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">إدارة العمليات</label>
                <textarea name="tasks_operations" rows="3" class="form-control">{{ old('tasks_operations', $card->tasks_operations ?? '') }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">المراقبة والتقييم والمسائلة والتعلم</label>
                <textarea name="tasks_mel" rows="3" class="form-control">{{ old('tasks_mel', $card->tasks_mel ?? '') }}</textarea>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">المنح والشراكات</label>
                <textarea name="tasks_grants" rows="3" class="form-control" placeholder="الجهات المانحة والشراكات المطلوبة ومهام كل جهة">{{ old('tasks_grants', $card->tasks_grants ?? '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- المحتوى والفقرات --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-card-list text-danger me-1"></i> المحتوى والفقرات</h6>
        <table class="table table-sm align-middle repeatable" data-prefix="content_items">
            <thead><tr><th>الفقرة</th><th>المحتوى</th><th>المسؤول</th><th>المدة</th><th style="width: 50px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة فقرة</button>
        <template class="row-template">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="content_items[__I__][item]" value="__item__"></td>
                <td><input type="text" class="form-control form-control-sm" name="content_items[__I__][content]" value="__content__"></td>
                <td><input type="text" class="form-control form-control-sm" name="content_items[__I__][responsible]" value="__responsible__"></td>
                <td><input type="text" class="form-control form-control-sm" name="content_items[__I__][duration]" value="__duration__"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
            </tr>
        </template>
    </div>

    {{-- اللوجستيات --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-truck text-danger me-1"></i> اللوجستيات المطلوبة</h6>
        <table class="table table-sm align-middle repeatable" data-prefix="logistics_items">
            <thead><tr><th>البند</th><th>المسؤول</th><th style="width: 50px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة بند</button>
        <template class="row-template">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="logistics_items[__I__][item]" value="__item__"></td>
                <td><input type="text" class="form-control form-control-sm" name="logistics_items[__I__][responsible]" value="__responsible__"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
            </tr>
        </template>
    </div>

    {{-- المشتريات --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-bag text-danger me-1"></i> المشتريات والمواد</h6>
        <table class="table table-sm align-middle repeatable" data-prefix="purchases_items">
            <thead><tr><th>البند</th><th>المسؤول</th><th style="width: 50px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة بند</button>
        <template class="row-template">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="purchases_items[__I__][item]" value="__item__"></td>
                <td><input type="text" class="form-control form-control-sm" name="purchases_items[__I__][responsible]" value="__responsible__"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
            </tr>
        </template>
    </div>

    {{-- الإعلام والتوثيق --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-megaphone text-danger me-1"></i> الإعلام والتوثيق</h6>
        <table class="table table-sm align-middle repeatable" data-prefix="media_items">
            <thead><tr><th>التغطية المطلوبة</th><th>المسؤول</th><th style="width: 50px;"></th></tr></thead>
            <tbody></tbody>
        </table>
        <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة تغطية</button>
        <template class="row-template">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="media_items[__I__][coverage]" value="__coverage__"></td>
                <td><input type="text" class="form-control form-control-sm" name="media_items[__I__][responsible]" value="__responsible__"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
            </tr>
        </template>
    </div>

    {{-- الموارد البشرية والمواصلات --}}
    <div class="form-card mb-3">
        <div class="row">
            <div class="col-md-6 mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-person-badge text-danger me-1"></i> الموارد البشرية</h6>
                <textarea name="hr_notes" rows="4" class="form-control" placeholder="ملاحظة: يتم التجمع في كل منطقة أمام المبنى الرئيسي لكل مكتب (طلاب – كادر).">{{ old('hr_notes', $card->hr_notes ?? '') }}</textarea>
            </div>
            <div class="col-md-6">
                <h6 class="fw-bold mb-2"><i class="bi bi-bus-front text-danger me-1"></i> المواصلات</h6>
                <table class="table table-sm align-middle repeatable" data-prefix="transport_items">
                    <thead><tr><th>المواصلات المطلوبة</th><th>النوع</th><th>مسؤول التنسيق</th><th style="width: 44px;"></th></tr></thead>
                    <tbody></tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة</button>
                <template class="row-template">
                    <tr>
                        <td><input type="text" class="form-control form-control-sm" name="transport_items[__I__][request]" value="__request__"></td>
                        <td><input type="text" class="form-control form-control-sm" name="transport_items[__I__][type]" value="__type__"></td>
                        <td><input type="text" class="form-control form-control-sm" name="transport_items[__I__][responsible]" value="__responsible__"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
                    </tr>
                </template>
            </div>
        </div>
    </div>

    {{-- الموازنة --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack text-danger me-1"></i> موازنة الفعالية</h6>
        <table class="table table-sm align-middle repeatable" data-prefix="budget_items" id="budgetTable">
            <thead><tr><th>البند</th><th>شرح</th><th style="width: 150px;">الكلفة</th><th style="width: 50px;"></th></tr></thead>
            <tbody></tbody>
            <tfoot>
                <tr>
                    <th colspan="2" class="text-end">الإجمالي</th>
                    <th id="budgetTotal">0.00</th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
        <button type="button" class="btn btn-sm btn-outline-brand add-row"><i class="bi bi-plus-lg"></i> إضافة بند</button>
        <template class="row-template">
            <tr>
                <td><input type="text" class="form-control form-control-sm" name="budget_items[__I__][item]" value="__item__"></td>
                <td><input type="text" class="form-control form-control-sm" name="budget_items[__I__][description]" value="__description__"></td>
                <td><input type="number" step="0.01" min="0" class="form-control form-control-sm cost-input" name="budget_items[__I__][cost]" value="__cost__"></td>
                <td><button type="button" class="btn btn-sm btn-outline-danger del-row" aria-label="حذف الصف" title="حذف الصف"><i class="bi bi-x-lg" aria-hidden="true"></i></button></td>
            </tr>
        </template>
    </div>

    {{-- تقييم بعد الفعالية --}}
    <div class="form-card mb-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-clipboard-check text-danger me-1"></i> تقييم بعد الفعالية</h6>
        <textarea name="post_evaluation" rows="4" class="form-control">{{ old('post_evaluation', $card->post_evaluation ?? '') }}</textarea>
    </div>

    {{-- الإحالة --}}
    <div class="form-card mb-3 border-start border-4" style="border-color: var(--color-primary) !important;">
        <h6 class="fw-bold mb-3"><i class="bi bi-send text-danger me-1"></i> الإحالة للموافقة</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">يُحال إلى <span class="text-danger">*</span></label>
                <select name="referred_user_id" class="form-select @error('referred_user_id') is-invalid @enderror" required>
                    <option value="">— اختر المستخدم —</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) old('referred_user_id', $card->referred_user_id ?? 0) === $user->id)>{{ $user->name }}</option>
                    @endforeach
                </select>
                @error('referred_user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">ملاحظة الإحالة</label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}">
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-brand"><i class="bi bi-check-lg me-1"></i> حفظ وإحالة</button>
        <a href="{{ isset($card) ? route('admin.event-cards.show', $card) : route('admin.event-cards.index') }}" class="btn btn-outline-secondary">إلغاء</a>
    </div>
</form>
@endsection

@push('scripts')
<script>
    const seed = @json($seed);
    let idx = 1000;

    function addRow(table, data = {}) {
        const tpl = table.closest('.form-card').querySelector('.row-template');
        let html = tpl.innerHTML.replace(/__I__/g, idx);
        for (const key in data) {
            html = html.replaceAll('__' + key + '__', String(data[key]).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
        }
        html = html.replace(/__\w+__/g, '');
        const tbody = table.querySelector('tbody');
        tbody.insertAdjacentHTML('beforeend', html);
        // تسمية الحقول المُنشأة ديناميكيًا من رأس العمود ورقم الصف
        const heads = Array.from(table.querySelectorAll('thead th')).map(function (th) { return th.textContent.trim(); });
        const tr = tbody.lastElementChild, n = tbody.children.length;
        Array.from(tr.children).forEach(function (td, i) {
            td.querySelectorAll('input:not([type=hidden]), select, textarea').forEach(function (el) {
                if (!el.getAttribute('aria-label') && heads[i]) el.setAttribute('aria-label', heads[i] + ' — صف ' + n);
            });
        });
        idx++;
        recomputeTotal();
    }

    function recomputeTotal() {
        let total = 0;
        document.querySelectorAll('#budgetTable .cost-input').forEach(function (i) { total += parseFloat(i.value) || 0; });
        const el = document.getElementById('budgetTotal');
        if (el) el.textContent = total.toFixed(2);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.repeatable').forEach(function (table) {
            const prefix = table.dataset.prefix;
            (seed[prefix] || []).forEach(function (row) { addRow(table, row); });
        });

        document.querySelectorAll('.add-row').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const table = btn.closest('.form-card').querySelector('.repeatable');
                addRow(table);
            });
        });

        document.addEventListener('click', function (e) {
            if (e.target.closest('.del-row')) {
                e.target.closest('tr').remove();
                recomputeTotal();
            }
        });

        document.addEventListener('input', function (e) {
            if (e.target.classList.contains('cost-input')) recomputeTotal();
        });
    });
</script>
@endpush
