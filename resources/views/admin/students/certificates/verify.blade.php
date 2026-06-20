<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من الشهادة</title>
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Tajawal', sans-serif; background: #f1f5f9; display: flex; align-items: center; min-height: 100vh; }
        .verify-card { max-width: 600px; margin: 0 auto; border: none; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .verify-card .card-body { padding: 2rem; }
        .status-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 1rem; }
        .status-valid { background: #d1e7dd; color: #198754; }
        .status-invalid { background: #f8d7da; color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card verify-card">
            <div class="card-body text-center">
                <div class="status-icon {{ $certificate->exists ? 'status-valid' : 'status-invalid' }}">
                    <i class="bi {{ $certificate->exists ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                </div>

                <h4 class="mb-1">
                    @if ($certificate->exists)
                        شهادة صالحة
                    @else
                        شهادة غير صالحة
                    @endif
                </h4>
                <p class="text-muted small mb-4">{{ $certificate->certificate_number ?? '' }}</p>

                @if ($certificate->exists)
                    <div class="text-start border-top pt-3">
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">رقم الشهادة</div>
                            <div class="col-7 fw-medium"><code>{{ $certificate->certificate_number }}</code></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">اسم الطالب</div>
                            <div class="col-7 fw-medium">{{ $certificate->student->first_name_ar }} {{ $certificate->student->last_name_ar }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">كود الطالب</div>
                            <div class="col-7 fw-medium"><code>{{ $certificate->student->student_code }}</code></div>
                        </div>
                        @if ($certificate->enrollment?->course)
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">المقرر</div>
                            <div class="col-7 fw-medium">{{ $certificate->enrollment->course->name_ar }}</div>
                        </div>
                        @endif
                        @if ($certificate->enrollment?->period)
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">الفترة</div>
                            <div class="col-7 fw-medium">{{ $certificate->enrollment->period->name_ar }}</div>
                        </div>
                        @endif
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">تاريخ الإصدار</div>
                            <div class="col-7 fw-medium">{{ $certificate->issue_date?->format('Y-m-d') }}</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">تاريخ التحقق</div>
                            <div class="col-7 fw-medium">{{ $certificate->verified_at?->format('Y-m-d H:i') ?? '—' }}</div>
                        </div>
                    </div>

                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('admin.students.certificates.preview', $certificate) }}" class="btn btn-primary" target="_blank">
                            <i class="bi bi-eye me-1"></i> إظهار الشهادة كاملة
                        </a>
                    </div>
                @else
                    <p class="text-muted">هذه الشهادة غير موجودة في نظامنا</p>
                @endif
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</body>
</html>
