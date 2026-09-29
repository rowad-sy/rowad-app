@extends('admin.layouts.master')

@section('title', 'لوحة المستفيد')

@section('content')
<x-page-header :title="'مرحباً بك يا ' . $user->name" description="لوحة المستفيد - مؤسسة الرواد للتعاون والتنمية" />

<div class="row g-4">
    <div class="col-md-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-person-circle me-1"></i> معلومات الحساب
            </h5>
            <div class="info-grid" style="display:grid;grid-template-columns:1fr;gap:0.75rem;">
                <div class="info-item" style="padding:0.5rem 0.75rem;background:var(--color-surface-muted);border:1px solid var(--color-border);border-radius:6px;">
                    <span style="font-size:0.75rem;color:var(--color-text-muted);display:block;">الاسم</span>
                    <span style="font-size:0.9rem;font-weight:500;">{{ $user->name }}</span>
                </div>
                <div class="info-item" style="padding:0.5rem 0.75rem;background:var(--color-surface-muted);border:1px solid var(--color-border);border-radius:6px;">
                    <span style="font-size:0.75rem;color:var(--color-text-muted);display:block;">البريد الإلكتروني</span>
                    <span style="font-size:0.9rem;font-weight:500;" dir="ltr">{{ $user->email }}</span>
                </div>
                <div class="info-item" style="padding:0.5rem 0.75rem;background:var(--color-surface-muted);border:1px solid var(--color-border);border-radius:6px;">
                    <span style="font-size:0.75rem;color:var(--color-text-muted);display:block;">تاريخ التسجيل</span>
                    <span style="font-size:0.9rem;font-weight:500;">{{ $user->created_at->locale('ar')->translatedFormat('l d F Y') }}</span>
                </div>
            </div>
            <a href="{{ route('admin.profile') }}" class="btn btn-outline-primary mt-3 w-100">
                <i class="bi bi-pencil me-1"></i> تعديل الملف الشخصي
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="form-card">
            <h5 class="fw-bold mb-3">
                <i class="bi bi-info-circle me-1"></i> معلومات عن المؤسسة
            </h5>
            <p class="text-muted">مؤسسة الرواد للتعاون والتنمية هي مؤسسة تهدف إلى تقديم خدمات التعاون والتنمية المستدامة للمجتمع.</p>
            <p class="text-muted">يمكنك التواصل مع المؤسسة عبر البريد الإلكتروني أو زيارة أقرب مركز لكم.</p>
        </div>
    </div>
</div>
@endsection
