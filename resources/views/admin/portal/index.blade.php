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
        <div class="portal-bg" aria-hidden="true">
            <i class="bi bi-heart-pulse" style="--x:8%;--y:14%;--s:2.6rem;--k:0"></i>
            <i class="bi bi-people" style="--x:86%;--y:12%;--s:3.2rem;--k:1"></i>
            <i class="bi bi-house-heart" style="--x:14%;--y:70%;--s:3rem;--k:2"></i>
            <i class="bi bi-mortarboard" style="--x:82%;--y:72%;--s:2.8rem;--k:3"></i>
            <i class="bi bi-balloon-heart" style="--x:50%;--y:6%;--s:2.2rem;--k:4"></i>
            <i class="bi bi-tree" style="--x:92%;--y:44%;--s:2.4rem;--k:5"></i>
            <i class="bi bi-droplet" style="--x:5%;--y:40%;--s:2.2rem;--k:6"></i>
            <i class="bi bi-basket" style="--x:68%;--y:90%;--s:2.4rem;--k:7"></i>
            <i class="bi bi-bandaid d-none d-md-block" style="--x:30%;--y:88%;--s:2.2rem;--k:8"></i>
            <i class="bi bi-chat-heart d-none d-md-block" style="--x:24%;--y:28%;--s:2.0rem;--k:9"></i>
            <i class="bi bi-globe-europe-africa d-none d-md-block" style="--x:74%;--y:30%;--s:2.6rem;--k:10"></i>
            <i class="bi bi-book d-none d-md-block" style="--x:40%;--y:76%;--s:2.0rem;--k:11"></i>
            <i class="bi bi-hand-thumbs-up d-none d-md-block" style="--x:58%;--y:82%;--s:2.0rem;--k:12"></i>
            <i class="bi bi-flower1 d-none d-md-block" style="--x:95%;--y:86%;--s:2.2rem;--k:13"></i>
            <i class="bi bi-lightbulb d-none d-md-block" style="--x:3%;--y:88%;--s:2.0rem;--k:14"></i>
            <i class="bi bi-sun d-none d-md-block" style="--x:96%;--y:8%;--s:2.4rem;--k:15"></i>
        </div>
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
            <div class="portal-tagline" id="portalTagline" data-text="نعمل معًا ... نرقى معًا" role="heading" aria-level="1" aria-label="نعمل معًا ... نرقى معًا">
                <span class="tagline-text" aria-hidden="true"></span><span class="tagline-caret" aria-hidden="true"></span>
            </div>
            <div class="portal-orbit">
                {{-- اللوغو في الدائرة الوسطية --}}
                <div class="portal-center">
                    <img src="{{ asset('images/logo.png') }}" alt="لوغو مؤسسة الرواد للتعاون والتنمية">
                    <div class="portal-center-title">مؤسسة الرُّؤاد<br>للتعاون والتنمية</div>
                </div>

                <a href="https://www.alrowadngo.sy/wp-content/uploads/2026/06/profile-2026.pdf" target="_blank" rel="noopener" class="portal-btn" style="--angle:0deg; --n:0">
                    <span class="portal-btn-icon"><i class="bi bi-journal-text"></i></span>
                    <span>الملف التعريفي</span>
                </a>

                <a href="{{ route('admin.identities') }}" class="portal-btn" style="--angle:60deg; --n:1">
                    <span class="portal-btn-icon"><i class="bi bi-share-fill"></i></span>
                    <span>المعرفات الرئيسية</span>
                </a>

                <a href="{{ route('admin.events-calendar') }}" class="portal-btn" style="--angle:120deg; --n:2">
                    <span class="portal-btn-icon"><i class="bi bi-calendar3"></i></span>
                    <span>الفعاليات</span>
                </a>

                <a href="{{ route('admin.paths.tree') }}" class="portal-btn" style="--angle:180deg; --n:3">
                    <span class="portal-btn-icon"><i class="bi bi-diagram-3-fill"></i></span>
                    <span>المسارات والمشاريع</span>
                </a>

                <a href="https://statistics.alrowadngo.sy/?institution=rowad" target="_blank" rel="noopener" class="portal-btn" style="--angle:240deg; --n:4">
                    <span class="portal-btn-icon"><i class="bi bi-bar-chart-line-fill"></i></span>
                    <span>الإحصائيات</span>
                </a>

                <a href="https://www.youtube.com/watch?v=O-Z8fpXpnGY" target="_blank" rel="noopener" class="portal-btn" style="--angle:300deg; --n:5">
                    <span class="portal-btn-icon"><i class="bi bi-play-btn-fill"></i></span>
                    <span>البرومو</span>
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
<script>
    (function () {
        var box = document.getElementById('portalTagline');
        if (!box) return;
        var text = box.dataset.text, out = box.querySelector('.tagline-text');
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { out.textContent = text; box.classList.add('is-done'); return; }
        var i = 0;
        setTimeout(function type() {
            out.textContent = text.slice(0, ++i);
            if (i < text.length) { setTimeout(type, 85 + Math.random() * 60); } else { box.classList.add('is-done'); }
        }, 300);
    })();
</script>
</body>
</html>
