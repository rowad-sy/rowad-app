{{-- قالب مشترك لصفحات الأخطاء: خط Tajawal المحلي وRTL والوضعان الفاتح والداكن عبر app.css/app.js.
     المستخدم المصادَق عليه يرى زر الخروج (نموذج POST بمعرّف CSRF)، ولا يمنحه هذا أي وصول إضافي. --}}
@php
    $signedIn = false;
    try { $signedIn = auth()->check(); } catch (\Throwable $e) {}
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') - @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh; background: var(--color-bg-main);">
    <main class="text-center p-4" style="max-width:520px">
        <h1 class="display-1 fw-bold mb-3" style="color: var(--color-primary, #F37021)">@yield('code')</h1>
        <h4 class="mb-3">@yield('title')</h4>
        <p class="text-muted mb-4">@yield('message')</p>
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <a href="javascript:history.back()" class="btn btn-outline-secondary">العودة للصفحة السابقة</a>
            @if ($signedIn)
                <a href="{{ route('admin.home') }}" class="btn btn-primary">الذهاب للتطبيقات</a>
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">تسجيل الخروج</button>
                </form>
            @else
                <a href="{{ url('/') }}" class="btn btn-primary">الصفحة الرئيسية</a>
            @endif
        </div>
    </main>
</body>
</html>
