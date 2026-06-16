<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - غير مصرح</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700&display=swap" rel="stylesheet">
    @vite(['resources/js/app.js'])
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh;">
    <div class="text-center p-5">
        <h1 class="display-1 fw-bold text-danger mb-3">403</h1>
        <h4 class="mb-3">ليس لديك صلاحية للوصول إلى هذه الصفحة</h4>
        <p class="text-muted mb-4">إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع مدير النظام</p>
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary me-2">العودة للصفحة السابقة</a>
        <a href="{{ route('admin.home') }}" class="btn btn-primary">الذهاب للتطبيقات</a>
    </div>
</body>
</html>
