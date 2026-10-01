@extends('admin.layouts.master')

@section('title', 'شجرة المسارات والمشاريع')

@section('content')
<x-page-header :title="'شجرة المسارات والمشاريع'" :description="'المسارات وما يتبعها من مشاريع مع حالة كل مشروع'"
               :breadcrumb="[['label' => 'المشاريع'], ['label' => 'شجرة المسارات والمشاريع']]">
    <div class="d-flex flex-wrap gap-2">
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleAll(true)"><i class="bi bi-arrows-angle-expand me-1"></i> توسيع الكل</button>
        <button class="btn btn-outline-secondary btn-sm" onclick="toggleAll(false)"><i class="bi bi-arrows-angle-collapse me-1"></i> طيّ الكل</button>
        <a href="{{ route('admin.paths.export.excel') }}" class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel me-1"></i> Excel
        </a>
        <a href="{{ route('admin.paths.export.pdf') }}" target="_blank" class="btn btn-brand btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> PDF
        </a>
    </div>
</x-page-header>

<div class="tree-tagline" id="treeTagline" data-text="نعمل معًا ... نرقى معًا" aria-label="نعمل معًا ... نرقى معًا">
    <span class="tagline-text" aria-hidden="true"></span><span class="tagline-caret" aria-hidden="true"></span>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    @foreach ($statuses as $key => $label)
        <span class="status-pill {{ \App\Models\Admin\Project::STATUS_BADGES[$key] }}">
            {{ $label }} ({{ $statusCounts[$key] ?? 0 }})
        </span>
    @endforeach
</div>

<div id="treeScreen">
    @forelse ($paths as $path)
        <div class="tree-node" style="--i: {{ $loop->index }}">
            <button type="button" class="tree-branch-head" aria-expanded="true">
                <i class="bi bi-chevron-down tree-arrow" aria-hidden="true"></i>
                <i class="bi bi-signpost-split text-danger"></i>
                <span class="flex-grow-1">{{ $path->name }}</span>
                @if ($path->code)
                    <span class="badge text-bg-light border">{{ $path->code }}</span>
                @endif
                <span class="tree-count-badge">{{ $path->projects->count() }}</span>
            </button>
            <div class="tree-collapse"><div class="tree-children"><div class="tree-children-inner">
                @forelse ($path->projects as $project)
                    <a href="{{ route('admin.projects.overview', $project) }}" class="tree-leaf" style="--j: {{ $loop->index }}">
                        <span class="tree-leaf-name">
                            <i class="bi bi-diagram-2"></i>
                            {{ $project->name }}
                            @if ($project->code)
                                <span class="text-muted small">({{ $project->code }})</span>
                            @endif
                        </span>
                        <span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span>
                    </a>
                @empty
                    <div class="text-muted small px-3 py-2">لا توجد مشاريع في هذا المسار.</div>
                @endforelse
            </div></div></div>
        </div>
    @empty
        <div class="table-container text-center p-5 text-muted">
            <i class="bi bi-diagram-2 fs-1 d-block mb-2 opacity-50"></i>
            لا توجد مسارات بعد. أضف المسارات من صفحة المسارات.
        </div>
    @endforelse

    @if ($orphanProjects->isNotEmpty())
        <div class="tree-node" style="--i: {{ $paths->count() }}">
            <button type="button" class="tree-branch-head" aria-expanded="true" style="background: var(--color-hover);">
                <i class="bi bi-chevron-down tree-arrow" aria-hidden="true"></i>
                <i class="bi bi-inbox text-muted"></i>
                <span class="flex-grow-1">مشاريع بدون مسار</span>
                <span class="tree-count-badge" style="background: var(--color-text-muted);">{{ $orphanProjects->count() }}</span>
            </button>
            <div class="tree-collapse"><div class="tree-children"><div class="tree-children-inner">
                @foreach ($orphanProjects as $project)
                    <a href="{{ route('admin.projects.overview', $project) }}" class="tree-leaf" style="--j: {{ $loop->index }}">
                        <span class="tree-leaf-name">
                            <i class="bi bi-diagram-2"></i>
                            {{ $project->name }}
                            @if ($project->code) <span class="text-muted small">({{ $project->code }})</span> @endif
                        </span>
                        <span class="status-pill {{ $project->statusBadgeClass() }}">{{ $project->statusLabel() }}</span>
                    </a>
                @endforeach
            </div></div></div>
        </div>
    @endif
</div>

{{-- نسخة الطباعة A4 أفقي --}}
<div class="print-sheet">
    <div class="print-header">
        <div style="text-align: right;">
            <div style="font-weight: 800; font-size: 18px;">شجرة المسارات والمشاريع</div>
            <div style="font-size: 11px; color: #64748b;">مؤسسة الرواد للتعاون والتنمية</div>
        </div>
        <img src="{{ asset('images/logo.png') }}" alt="لوغو المؤسسة">
    </div>
    <table>
        <thead>
            <tr>
                <th style="width: 40px;">م/ت</th>
                <th>المسار</th>
                <th>اسم المشروع</th>
                <th style="width: 120px;">كود المشروع</th>
                <th style="width: 120px;">حالة المشروع</th>
            </tr>
        </thead>
        <tbody>
            @php $n = 0; @endphp
            @foreach ($paths as $path)
                @foreach ($path->projects as $project)
                    <tr>
                        <td>{{ ++$n }}</td>
                        <td>{{ $path->name }}</td>
                        <td>{{ $project->name }}</td>
                        <td>{{ $project->code ?? '—' }}</td>
                        <td>{{ $project->statusLabel() }}</td>
                    </tr>
                @endforeach
            @endforeach
            @foreach ($orphanProjects as $project)
                <tr>
                    <td>{{ ++$n }}</td>
                    <td>بدون مسار</td>
                    <td>{{ $project->name }}</td>
                    <td>{{ $project->code ?? '—' }}</td>
                    <td>{{ $project->statusLabel() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div style="margin-top: 8px; font-size: 10px; color: #64748b; text-align: left;">
        تاريخ الإصدار: {{ now()->translatedFormat('d F Y') }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    function setNode(node, open) {
        node.classList.toggle('collapsed', !open);
        var head = node.querySelector('.tree-branch-head');
        if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    function toggleAll(open) {
        document.querySelectorAll('#treeScreen .tree-node').forEach(function (node) { setNode(node, open); });
    }
    document.querySelectorAll('#treeScreen .tree-branch-head').forEach(function (head) {
        head.addEventListener('click', function () {
            var node = head.closest('.tree-node');
            setNode(node, node.classList.contains('collapsed'));
        });
    });

    // تأثير الكتابة للعبارة (يُعرض فورًا عند تفضيل تقليل الحركة)
    (function () {
        var box = document.getElementById('treeTagline');
        if (!box) return;
        var text = box.dataset.text, out = box.querySelector('.tagline-text');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { out.textContent = text; box.classList.add('is-done'); return; }
        var i = 0;
        (function type() {
            out.textContent = text.slice(0, ++i);
            if (i < text.length) { setTimeout(type, 85 + Math.random() * 60); } else { box.classList.add('is-done'); }
        })();
    })();
</script>
@endpush
