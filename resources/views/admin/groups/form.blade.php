@extends('admin.layouts.master')

@section('title', isset($group) ? 'تعديل مجموعة' : 'إضافة مجموعة')

@push('styles')
<style>
    .users-list { max-height: 400px; overflow-y: auto; border: 1px solid var(--color-border); border-radius: 6px; padding: 0.75rem; }
    .users-list .form-check { padding: 0.35rem 0.5rem 0.35rem 0.5rem; margin: 0; border-radius: 4px; display: flex; align-items: center; gap: 0.5rem; }
    .users-list .form-check .form-check-input { float: none; margin: 0; }
    .users-list .form-check:hover { background: var(--color-hover); }
    .users-list .form-check.hidden { display: none; }
    .users-list .form-check small { color: var(--color-text-muted); }
    .user-count { font-size: 0.85rem; color: var(--color-text-muted); }
</style>
@endpush

@section('content')
<x-page-header :title="isset($group) ? 'تعديل المجموعة' : 'إضافة مجموعة'"
               :breadcrumb="[['label' => 'المجموعات', 'url' => route('admin.groups.index')], ['label' => isset($group) ? $group->name : 'جديد']]" />

<div class="row">
    <div class="col-md-8">
        <div class="form-card">
            <form method="POST"
                  action="{{ isset($group) ? route('admin.groups.update', $group) : route('admin.groups.store') }}">
                @csrf
                @if (isset($group))
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label class="form-label">اسم المجموعة <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $group->name ?? '') }}" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" rows="3"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $group->description ?? '') }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label" for="userSearch">الأعضاء</label>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <input type="search" class="form-control form-control-sm" id="userSearch" placeholder="بحث بالاسم أو البريد..." style="max-width:280px;" oninput="filterUsers(this.value)">
                        {{-- يعمل على المستخدمين الظاهرين حاليًا فقط (بعد البحث) --}}
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllUsers(true)">تحديد الظاهرين</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllUsers(false)">إلغاء تحديد الظاهرين</button>
                        <span class="user-count" id="userCount" role="status" aria-live="polite"></span>
                    </div>
                    <div class="users-list" id="usersList">
                        @foreach ($users as $user)
                            <div class="form-check user-item" data-search="{{ $user->name }} {{ $user->email }} {{ $user->type }}">
                                <input type="checkbox" name="users[]" value="{{ $user->id }}"
                                       class="form-check-input user-checkbox"
                                       id="user_{{ $user->id }}"
                                       {{ in_array($user->id, old('users', isset($group) ? $group->users->pluck('id')->toArray() : [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="user_{{ $user->id }}">
                                    {{ $user->name }}
                                    <small>({{ $user->email }})</small>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-grid d-sm-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1" aria-hidden="true"></i> حفظ
                    </button>
                    <a href="{{ route('admin.groups.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function filterUsers(query) {
    query = query.toLowerCase().trim();
    var items = document.querySelectorAll('.user-item');
    var count = 0;
    items.forEach(function (item) {
        var matched = !query || item.dataset.search.toLowerCase().includes(query);
        item.classList.toggle('hidden', !matched);
        if (matched) count++;
    });
    document.getElementById('userCount').textContent = '(' + count + ' مستخدم)';
}

function selectAllUsers(select) {
    document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(function (cb) {
        var item = cb.closest('.user-item');
        if (item && !item.classList.contains('hidden')) {
            cb.checked = select;
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    filterUsers('');
});
</script>
@endpush
