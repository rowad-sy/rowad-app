<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <title>{{ $title ?? 'تسجيل الدخول' }} | مؤسسة الرواد</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at 12% 18%, rgba(255, 138, 101, 0.25), transparent 42%),
                radial-gradient(circle at 88% 82%, rgba(255, 193, 7, 0.22), transparent 42%),
                linear-gradient(135deg, #F37021 0%, #FF8F4D 45%, #FFB74D 100%);
            padding: 1.5rem;
            position: relative;
        }
        [data-theme="dark"] .auth-page {
            background:
                radial-gradient(circle at 12% 18%, rgba(255, 140, 0, 0.18), transparent 42%),
                radial-gradient(circle at 88% 82%, rgba(0, 230, 118, 0.12), transparent 42%),
                linear-gradient(135deg, #0B0C10 0%, #0F1117 60%, #181B23 100%);
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: var(--color-card);
            border-radius: 1.25rem;
            border: 1px solid var(--color-border);
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
            padding: 2.5rem 2rem;
            position: relative;
        }
        .auth-logo {
            width: 88px;
            height: 88px;
            background: #fff;
            border-radius: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            padding: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .auth-logo img { width: 100%; height: 100%; object-fit: contain; }
        .auth-title {
            font-weight: 800;
            font-size: 1.35rem;
            color: var(--color-text-main);
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            color: var(--color-text-muted);
            font-size: 0.875rem;
            margin-bottom: 1.75rem;
        }
        .auth-card .form-label { font-weight: 500; font-size: 0.875rem; color: var(--color-text-main); }
        .auth-card .form-control,
        .auth-card .form-select { border-radius: 0.6rem; padding: 0.65rem 0.8rem; font-size: 0.9rem; }
        .btn-auth {
            background: linear-gradient(135deg, var(--color-gradient-start), var(--color-gradient-end));
            border: none;
            color: #fff;
            border-radius: 0.6rem;
            padding: 0.7rem 1rem;
            font-weight: 700;
            font-size: 0.98rem;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-auth:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(243,112,33,0.35); color:#fff; }
        .btn-auth:disabled { opacity: 0.6; transform: none; }
        .auth-theme-toggle {
            position: absolute;
            top: 1rem;
            left: 1rem;
        }
        .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.875rem; color: var(--color-text-muted); }
        .auth-footer a { color: var(--color-text-main); font-weight: 600; text-decoration: none; }
        .auth-footer a:hover { text-decoration: underline; }
        .auth-divider { text-align: center; margin: 1.25rem 0; position: relative; }
        .auth-divider::before { content: ''; position: absolute; top: 50%; right: 0; left: 0; height: 1px; background: var(--color-border); }
        .auth-divider span { background: var(--color-card); padding: 0 0.75rem; position: relative; color: var(--color-text-muted); font-size: 0.8rem; }
        .alert-session { border-radius: 0.5rem; font-size: 0.85rem; padding: 0.5rem 0.75rem; }
    </style>
</head>
<body class="auth-page">
    <div class="auth-card">
        <button class="btn btn-light btn-sm theme-toggle-btn auth-theme-toggle" type="button" title="الوضع الليلي/النهاري">
            <i class="bi bi-moon-stars"></i>
        </button>
        <div class="text-center">
            <div class="auth-logo">
                <img src="{{ asset('images/logo.png') }}" alt="لوغو مؤسسة الرواد للتعاون والتنمية">
            </div>
            <h1 class="auth-title">{{ $title ?? 'مؤسسة الرواد للتعاون والتنمية' }}</h1>
            <p class="auth-subtitle">{{ $description ?? '' }}</p>
        </div>

        @if (session('status'))
            <div class="alert alert-success alert-session" role="alert">
                <i class="bi bi-check-circle me-1"></i>
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-session" role="alert">
                <i class="bi bi-exclamation-triangle me-1"></i>
                {{ $errors->first() }}
            </div>
        @endif

        {{ $slot }}
    </div>
    @stack('scripts')
    @fluxScripts
</body>
</html>
