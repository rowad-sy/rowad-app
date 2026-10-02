@php
    $selCenter = old('center_id', $pivot->center_id ?? '');
    $selProject = old('project_id', $pivot->project_id ?? '');
    $selCohort = old('cohort_id', $pivot->cohort_id ?? '');
@endphp

<div class="mb-3">
    <label class="form-label">المستخدم <span class="text-danger">*</span></label>
    <select name="user_id" class="form-control @error('user_id') is-invalid @enderror" {{ isset($pivot) ? 'disabled' : '' }} required>
        <option value="">— اختر المستخدم —</option>
        @foreach ($users as $u)
            <option value="{{ $u->id }}" @selected((int) old('user_id', $user->id ?? 0) === $u->id)>
                {{ $u->name }} ({{ $u->email }}){{ $u->type === 'super-admin' ? ' — سوبر-أدن' : '' }}
            </option>
        @endforeach
    </select>
    @if (isset($pivot))
        <input type="hidden" name="user_id" value="{{ $user->id }}">
    @endif
    @error('user_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<div class="alert alert-light border small">
    <i class="bi bi-info-circle me-1"></i>
    <b>نطاق سريان الدور:</b> اترك الحقول فارغة ليسري الدور على <b>كل المنظمة</b>؛
    أو حدد المركز/المشروع/الفوج فينحصر نشاط المستخدم فيه. النطاق هنا <b>يتغلب</b> على أي
    نطاق مسجل في صلاحيات المجموعة نفسها.
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label class="form-label">🏢 المركز</label>
        <select name="center_id" class="form-control @error('center_id') is-invalid @enderror">
            <option value="">الكل</option>
            @foreach ($centers as $c)
                <option value="{{ $c->id }}" @selected($selCenter != '' && (int) $selCenter === $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
        @error('center_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">📌 المشروع</label>
        <select name="project_id" class="form-control @error('project_id') is-invalid @enderror">
            <option value="">الكل</option>
            @foreach ($projects as $p)
                <option value="{{ $p->id }}" @selected($selProject != '' && (int) $selProject === $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
        @error('project_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4 mb-3">
        <label class="form-label">🎓 الفوج</label>
        <select name="cohort_id" class="form-control @error('cohort_id') is-invalid @enderror">
            <option value="">الكل</option>
            @foreach ($cohorts as $co)
                <option value="{{ $co->id }}" @selected($selCohort != '' && (int) $selCohort === $co->id)>{{ $co->name }}{{ $co->project ? ' — ' . $co->project->name : '' }}</option>
            @endforeach
        </select>
        @error('cohort_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
</div>
