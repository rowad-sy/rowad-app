<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-bootstrap', ['title' => 'اختيار نوع الحساب', 'description' => 'اختر نوع الحساب المناسب للدخول أو إنشاء حساب جديد'])] class extends Component {
    //
}; ?>

<div>
    <div class="row g-3">
        <div class="col-12">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary w-100 py-3 text-start d-flex align-items-center gap-3" style="border-radius:0.75rem;border:2px solid #e9ecef;">
                <span style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#0d6efd,#0a58ca);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.25rem;flex-shrink:0;">
                    <i class="bi bi-box-arrow-in-right"></i>
                </span>
                <span>
                    <strong style="font-size:1rem;color:#1e293b;">تسجيل الدخول</strong>
                    <br><small style="color:#6c757d;">للموظفين والمستفيدين والطلاب</small>
                </span>
                <i class="bi bi-chevron-left me-auto" style="color:#adb5bd;"></i>
            </a>
        </div>
    </div>
</div>
