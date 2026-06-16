<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'تسجيل الدخول' }} | مؤسسة الرواد</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
            padding: 1.5rem;
        }
        .auth-card {
            width: 100%;
            max-width: 440px;
            background: #fff;
            border-radius: 1rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 8px 40px rgba(0,0,0,0.08);
            padding: 2.5rem 2rem;
        }
        .auth-logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #1e293b, #334155);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.75rem;
            color: #fff;
        }
        .auth-title {
            font-weight: 700;
            font-size: 1.25rem;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }
        .auth-subtitle {
            color: #6c757d;
            font-size: 0.875rem;
            margin-bottom: 1.75rem;
        }
        .auth-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #6c757d;
        }
        .auth-footer a {
            color: #1e293b;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }
        .auth-card .form-label {
            font-weight: 500;
            font-size: 0.875rem;
            color: #374151;
        }
        .auth-card .form-control,
        .auth-card .form-select {
            border-radius: 0.5rem;
            padding: 0.6rem 0.75rem;
            font-size: 0.9rem;
            border-color: #d1d5db;
        }
        .auth-card .form-control:focus,
        .auth-card .form-select:focus {
            border-color: #1e293b;
            box-shadow: 0 0 0 3px rgba(30,41,59,0.12);
        }
        .btn-auth {
            background: #1e293b;
            border: none;
            color: #fff;
            border-radius: 0.5rem;
            padding: 0.65rem 1rem;
            font-weight: 600;
            font-size: 0.95rem;
            width: 100%;
            transition: all 0.2s;
        }
        .btn-auth:hover {
            background: #0f172a;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(30,41,59,0.25);
        }
        .btn-auth:disabled {
            opacity: 0.6;
            transform: none;
        }
        .auth-divider {
            text-align: center;
            margin: 1.25rem 0;
            position: relative;
        }
        .auth-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            left: 0;
            height: 1px;
            background: #e5e7eb;
        }
        .auth-divider span {
            background: #fff;
            padding: 0 0.75rem;
            position: relative;
            color: #9ca3af;
            font-size: 0.8rem;
        }
        .alert-session {
            border-radius: 0.5rem;
            font-size: 0.85rem;
            padding: 0.5rem 0.75rem;
        }
    </style>
</head>
<body class="auth-page">
    <div class="auth-card">
        <div class="text-center">
            <div class="auth-logo">
                <i class="bi bi-building"></i>
            </div>
            <h1 class="auth-title">{{ $title ?? 'مؤسسة الرواد' }}</h1>
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
