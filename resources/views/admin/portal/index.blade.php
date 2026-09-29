<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <title>مؤسسة الرواد للتعاون والتنمية</title>
    <link rel="preload" href="{{ asset('fonts/tajawal/tajawal-arabic-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a href="#portalMain" class="visually-hidden-focusable btn btn-primary btn-sm position-absolute" style="top:.5rem;right:.5rem;z-index:10">تخطي إلى المحتوى</a>
    <div class="portal-page">
        <header class="portal-topbar">
            <span class="text-muted small text-truncate">
                <i class="bi bi-person-circle me-1" aria-hidden="true"></i>{{ auth()->user()->name }}
            </span>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light btn-sm theme-toggle-btn" type="button" aria-label="تبديل الوضع الليلي/النهاري" title="تبديل الوضع الليلي/النهاري">
                    <i class="bi bi-moon-stars" aria-hidden="true"></i>
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-speedometer2 me-1" aria-hidden="true"></i><span class="d-none d-sm-inline">لوحة التحكم</span>
                </a>
            </div>
        </header>

        <main class="portal-hero" id="portalMain">
            <div class="portal-orbit">
                {{-- اللوغو في الدائرة الوسطية --}}
                <div class="portal-center">
                    <img src="{{ asset('images/logo.png') }}" alt="لوغو مؤسسة الرواد للتعاون والتنمية">
                    <div class="portal-center-title">مؤسسة الرُّؤاد<br>للتعاون والتنمية</div>
                </div>

                <a href="https://www.alrowadngo.sy/wp-content/uploads/2026/06/profile-2026.pdf" target="_blank" rel="noopener" class="portal-btn" style="--angle:-90deg">
                    <span class="portal-btn-icon"><i class="bi bi-journal-text"></i></span>
                    <span>الملف التعريفي</span>
                </a>

                <a href="{{ route('admin.identities') }}" class="portal-btn" style="--angle:-18deg">
                    <span class="portal-btn-icon"><i class="bi bi-share-fill"></i></span>
                    <span>المعرفات الرئيسية</span>
                </a>

                <a href="{{ route('admin.events-calendar') }}" class="portal-btn" style="--angle:54deg">
                    <span class="portal-btn-icon"><i class="bi bi-calendar3"></i></span>
                    <span>الفعاليات</span>
                </a>

                <a href="{{ route('admin.paths.tree') }}" class="portal-btn" style="--angle:126deg">
                    <span class="portal-btn-icon"><i class="bi bi-diagram-3-fill"></i></span>
                    <span>المسارات والمشاريع</span>
                </a>

                <a href="https://app.powerbi.com/view?r=eyJrIjoiN2U0ZTIwMTQtODRlNS00Mzg2LWI4YjMtMDFhMWY0YzdhMzVlIiwidCI6IjZlYjI4YmIwLTQ3MDQtNGQwOS05MGMzLTY4NGQ1YTFhMWVlYiIsImMiOjl9" target="_blank" rel="noopener" class="portal-btn" style="--angle:198deg">
                    <span class="portal-btn-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                    <span>الإحصائيات</span>
                </a>
            </div>

            {{-- الإجراء الأساسي: الدخول للعمل اليومي --}}
            <a href="{{ route('admin.home') }}" class="btn btn-primary btn-lg portal-cta">
                <i class="bi bi-grid-3x3-gap me-2" aria-hidden="true"></i>الدخول إلى التطبيقات
            </a>
        </main>

        <div class="text-center pb-4">
            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-link btn-sm text-muted text-decoration-none">
                    <i class="bi bi-box-arrow-left me-1"></i>تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</body>
</html>
