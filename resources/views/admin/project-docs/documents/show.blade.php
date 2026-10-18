@extends('admin.layouts.master')

@section('title', 'عرض وثيقة')

@section('content')
@php
    $isCreator = auth()->id() === (int) $document->created_by;
    $pageCount = max(1, (int) $document->page_count);
    $badge = [
        'draft' => 'secondary',
        'under_review' => 'warning text-dark',
        'approved' => 'success',
        'rejected' => 'danger',
    ][$document->status] ?? 'secondary';
@endphp

@php $tone = ['success' => 'success', 'warning' => 'warning', 'danger' => 'danger', 'info' => 'info', 'primary' => 'brand'][$badge] ?? 'neutral'; @endphp
<x-page-header :title="$document->title ?: $document->template->title_ar"
               :breadcrumb="[['label' => 'الوثائق', 'url' => route('admin.project-docs.documents.index')], ['label' => 'عرض']]">
    <x-slot:meta><div class="mt-2"><x-status-badge :tone="$tone">{{ $document::STATUSES[$document->status] }}</x-status-badge></div></x-slot:meta>
</x-page-header>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        @if ($document->isExternal())
            <span class="text-muted small">نوع الوثيقة:</span>
            <strong><i class="bi bi-file-earmark-pdf text-danger"></i> ملف خارجي (PDF)</strong>
        @else
            <span class="text-muted small">من قالب:</span>
            <strong>{{ $document->template->title_ar }}</strong>
            <x-status-badge>V{{ $document->template_version }}</x-status-badge>
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.project-docs.documents.help') }}" class="btn btn-outline-info" title="معلومات ونصائح">
            <i class="bi bi-question-circle"></i> معلومات
        </a>
        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'view')
        <a href="{{ route('admin.project-docs.documents.print', $document) }}" target="_blank" class="btn btn-outline-dark">
            @if ($document->isExternal())
                <i class="bi bi-file-earmark-arrow-down"></i> تحميل الملف الأصلي
            @else
                <i class="bi bi-printer"></i> طباعة A4
            @endif
        </a>
        @endcanPermission
        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'create')
        @if (!$document->isExternal())
        <form method="POST" action="{{ route('admin.project-docs.documents.duplicate', $document) }}"
              onsubmit="return confirm('إنشاء نسخة جديدة من هذه الوثيقة بكل محتواها لتعديلها؟')">
            @csrf
            <button class="btn btn-outline-primary"><i class="bi bi-files"></i> نسخة جديدة</button>
        </form>
        @endif
        @endcanPermission
        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'edit')
        @if (in_array($document->status, ['draft', 'rejected'], true) && !$document->isExternal())
        <a href="{{ route('admin.project-docs.documents.edit', $document) }}" class="btn btn-primary">
            <i class="bi bi-pencil"></i> تعبئة/تعديل
        </a>
        @endif
        @if ($document->status === 'draft')
        <form method="POST" action="{{ route('admin.project-docs.documents.submit', $document) }}"
              onsubmit="return confirm('إرسال الوثيقة للمراجعة؟ ستُقفل كل الأقسام.')">
            @csrf
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <select name="reviewer_id" class="form-select form-select-sm" style="min-width: 190px;" required
                        aria-label="المُحال إليه للمراجعة">
                    <option value="">المُحال إليه للمراجعة...</option>
                    @foreach ($reviewers as $u)
                        <option value="{{ $u->id }}" {{ old('reviewer_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-success text-nowrap"><i class="bi bi-send"></i> إرسال للمراجعة</button>
            </div>
        </form>
        @endif
        @if ($document->status === 'rejected' && $isCreator)
        <form method="POST" action="{{ route('admin.project-docs.documents.reopen', $document) }}">
            @csrf
            <button class="btn btn-outline-warning"><i class="bi bi-unlock"></i> إعادة فتح للتعديل</button>
        </form>
        @endif
        @endcanPermission
        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'delete')
        @if ($document->status === 'draft')
        <form method="POST" action="{{ route('admin.project-docs.documents.destroy', $document) }}"
              onsubmit="return confirm('حذف الوثيقة نهائياً؟')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" aria-label="حذف" title="حذف"><i class="bi bi-trash" aria-hidden="true"></i></button>
        </form>
        @endif
        @endcanPermission
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-light">بيانات الوثيقة</div>
            <div class="card-body">
                <div class="row small">
                    <div class="col-md-4 mb-1"><span class="text-muted">المشروع:</span> <strong>{{ $document->project?->name ?? '—' }}</strong></div>
                    <div class="col-md-4 mb-1"><span class="text-muted">الفترة:</span> <strong>{{ $document->period ?? '—' }}</strong></div>
                    <div class="col-md-4 mb-1"><span class="text-muted">المنشئ:</span> <strong>{{ $document->creator?->name }}</strong></div>
                    <div class="col-md-4 mb-1"><span class="text-muted">المُحال إليه للمراجعة:</span> <strong>{{ $document->assignee?->name ?? '—' }}</strong></div>
                    <div class="col-md-4 mb-1"><span class="text-muted">الاعتماد:</span>
                        <strong>{{ $document->signed_at ? \Illuminate\Support\Carbon::parse($document->signed_at)->format('d/m/Y H:i') : '—' }}</strong>
                    </div>
                    <div class="col-md-4 mb-1"><span class="text-muted">عدد الصفحات:</span> <strong>{{ $pageCount }}</strong></div>
                    <div class="col-md-4 mb-1"><span class="text-muted">آخر تحديث:</span> <strong>{{ $document->updated_at?->format('d/m/Y H:i') }}</strong></div>
                </div>
            </div>
        </div>

        @if ($document->isExternal())
            @php $srcFile = $document->linkedFiles->sortByDesc('id')->first(); @endphp
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold"><i class="bi bi-paperclip"></i> الملف الأصلي</span>
                    @if ($srcFile)
                        <div class="d-flex gap-2">
                            <a class="btn btn-sm btn-outline-info" href="{{ $srcFile->fileUrl() }}" target="_blank" rel="noopener">
                                <i class="bi bi-box-arrow-up-right"></i> عرض في تبويب جديد
                            </a>
                            <a class="btn btn-sm btn-outline-dark" href="{{ route('admin.project-docs.documents.file', $document) }}">
                                <i class="bi bi-download"></i> تنزيل الملف
                            </a>
                        </div>
                    @endif
                </div>
                <div class="card-body small">
                    @if ($srcFile)
                        <div class="row">
                            <div class="col-md-6 mb-1"><span class="text-muted">اسم الملف:</span> <strong dir="ltr">{{ $srcFile->file_name ?: $srcFile->title }}</strong></div>
                            <div class="col-md-3 mb-1"><span class="text-muted">الحجم:</span> <strong>{{ $srcFile->file_size ? number_format($srcFile->file_size / 1024, 1) . ' كب' : '—' }}</strong></div>
                            <div class="col-md-3 mb-1"><span class="text-muted">التصنيف:</span> <strong>{{ $srcFile->categoryLabel() }}</strong></div>
                            <div class="col-md-6 mb-1"><span class="text-muted">تاريخ الوثيقة:</span> <strong>{{ $srcFile->document_date?->format('d/m/Y') ?? '—' }}</strong></div>
                        </div>
                        @canPermission('App\Models\Admin\ProjectDocs\UploadedDocument', 'view')
                            <div class="text-muted mt-2">
                                <i class="bi bi-archive"></i>
                                محفوظ في أرشيف الوثائق برقم
                                <a href="{{ route('admin.documents-archive.show', $srcFile) }}">#{{ $srcFile->id }}</a>
                            </div>
                        @endcanPermission
                    @else
                        <span class="text-danger"><i class="bi bi-exclamation-triangle"></i> لا يوجد ملف مرتبط — قد يكون حُذف من الأرشيف.</span>
                    @endif
                </div>
            </div>
        @endif

        @foreach ($document->sections() as $section)
            @php
                $block = $document->blocks->firstWhere('block_key', $section['key'] ?? null);
                $filled = $block && $document->isBlockComplete($block);
                $sectionPage = $block?->page_number ?? 1;
                $typeLabels = ['fields' => 'حقول', 'paragraph' => 'فقرة', 'table' => 'جدول', 'list' => 'قائمة'];
            @endphp
            <div class="card mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold">{{ $section['title'] ?? $section['key'] }}</span>
                    <span>
                        <span class="badge bg-light text-dark border">{{ $typeLabels[$section['type']] ?? $section['type'] }}</span>
                        @if ($pageCount > 1)
                            <span class="badge bg-primary-subtle text-primary">صفحة {{ $sectionPage }}</span>
                        @endif
                        @if (!empty($section['assignee_role']))
                            <x-status-badge tone="info">يُعبأ بواسطة: {{ $section['assignee_role'] }}</x-status-badge>
                        @endif
                        @if ($filled)
                            <span class="badge bg-success-subtle text-success">معبأ</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">ناقص</span>
                        @endif
                        @if ($block?->locked)
                            <x-status-badge tone="warning"><i class="bi bi-lock"></i> مقفول</x-status-badge>
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    @include('admin.project-docs.partials.block-view', ['section' => $section, 'block' => $block])
                    @if ($block?->updated_by)
                        <div class="small text-muted mt-2">
                            <i class="bi bi-person"></i> آخر تحديث: {{ $block->updater?->name ?? '—' }} · {{ $block->updated_at?->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="col-lg-4">
        @canPermission('App\Models\Admin\ProjectDocs\AnnexDocument', 'edit')
        @php
            $isReviewer = $document->assigned_to !== null && (int) $document->assigned_to === (int) auth()->id();
            $showComment = $document->status !== 'under_review' || $isReviewer;
            $canReassign = $isReviewer || auth()->user()->type === 'super-admin';
        @endphp
        @if ($document->status === 'under_review')
            @if ($isReviewer)
            <div class="card mb-3">
                <div class="card-header bg-light fw-bold">الاعتماد / الرفض</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.project-docs.documents.signoff', $document) }}">
                        @csrf
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" value="approve" id="aApproved">
                                <label class="form-check-label" for="aApproved">اعتماد نهائي</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" value="reject" id="aRejected">
                                <label class="form-check-label" for="aRejected">رفض</label>
                            </div>
                        </div>
                        <textarea name="note" rows="3" class="form-control mb-2" placeholder="ملاحظة / تعليل..."></textarea>
                        <button class="btn btn-success w-100">توقيع</button>
                    </form>
                </div>
            </div>
            @else
            <div class="card mb-3">
                <div class="card-header bg-light fw-bold">حالة المراجعة</div>
                <div class="card-body">
                    @if ($document->assigned_to)
                        <div class="alert alert-info py-2 mb-0 small">
                            <i class="bi bi-person-check"></i> بانتظار مراجعة <strong>{{ $document->assignee?->name }}</strong> — الاعتماد والتعليق منصوصان له وحده.
                        </div>
                    @else
                        <div class="alert alert-warning py-2 mb-0 small">
                            <i class="bi bi-exclamation-triangle"></i> وثيقة أُرسلت قبل نظام الإحالة — <strong>غير مُحال إليها</strong>، ولا يمكن اعتمادها حتى تُحال.
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if ($canReassign)
            <div class="card mb-3">
                <div class="card-header bg-light fw-bold"><i class="bi bi-arrow-repeat"></i> إعادة الإحالة</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.project-docs.documents.reassign', $document) }}" class="d-flex gap-2">
                        @csrf
                        <select name="reviewer_id" class="form-select form-select-sm" required aria-label="المراجع الجديد">
                            <option value="">اختر المراجع...</option>
                            @foreach ($reviewers as $u)
                                <option value="{{ $u->id }}" {{ old('reviewer_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-primary btn-sm text-nowrap">إحالة</button>
                    </form>
                </div>
            </div>
            @endif
        @endif

        @if ($showComment)
        <div class="card mb-3">
            <div class="card-header bg-light fw-bold">إضافة ملاحظة (تعليق)</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.project-docs.documents.signoff', $document) }}">
                    @csrf
                    <input type="hidden" name="action" value="comment">
                    <textarea name="note" rows="2" class="form-control mb-2" placeholder="ملاحظتك..."></textarea>
                    <button class="btn btn-outline-secondary w-100">إضافة</button>
                </form>
            </div>
        </div>
        @endif
        @endcanPermission

        <div class="card mb-3">
            <div class="card-header bg-light fw-bold"><i class="bi bi-pen"></i> سجل الاعتمادات</div>
            <div class="card-body p-0">
                @forelse ($document->signoffs as $signoff)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between">
                            <strong>{{ $signoff->user?->name ?? '—' }}</strong>
                            @php $actionBadge = [
                                'approve' => 'success', 'reject' => 'danger', 'comment' => 'secondary',
                            ][$signoff->action] ?? 'secondary'; @endphp
                            <span class="badge bg-{{ $actionBadge }}">
                                {{ ['approve' => 'اعتماد', 'reject' => 'رفض', 'comment' => 'تعليق'][$signoff->action] }}
                            </span>
                        </div>
                        <div class="small text-muted">
                            {{ $signoff->role_label ?? 'بدون مسمى' }} · {{ $signoff->created_at?->format('d/m/Y H:i') }}
                        </div>
                        @if ($signoff->note) <div class="small mt-1">{{ $signoff->note }}</div> @endif
                    </div>
                @empty
                    <div class="p-3 text-muted">لا اعتمادات بعد</div>
                @endforelse
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light fw-bold"><i class="bi bi-clock-history"></i> الشريط الزمني</div>
            <div class="card-body p-0">
                @forelse ($document->workflowActions->sortByDesc('id') as $action)
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between">
                            <span class="badge bg-light border text-dark">{{ $action->action }}</span>
                            <span class="small text-muted">{{ $action->created_at?->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="small mt-1">
                            @if ($action->from_user)
                                <i class="bi bi-person"></i> {{ $action->from_user->name }}
                            @endif
                            @if ($action->to_user)
                                → <i class="bi bi-person"></i> {{ $action->to_user->name }}
                            @endif
                        </div>
                        @if ($action->note) <div class="small text-muted mt-1">{{ $action->note }}</div> @endif
                    </div>
                @empty
                    <div class="p-3 text-muted">لا إجراءات مسجلة</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection