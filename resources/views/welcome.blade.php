<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مؤسسة الرواد للتنمية</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=tajawal:400,500,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Tajawal', sans-serif; background: #f8f9fa; }
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e293b 0%, #334155 50%, #1e293b 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            pointer-events: none;
        }
        .hero-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(245,158,11,0.3);
        }
        .hero h1 {
            font-size: 2.75rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
        }
        .hero p {
            font-size: 1.1rem;
            opacity: 0.85;
            max-width: 560px;
            line-height: 1.8;
            margin-bottom: 2.5rem;
        }
        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .btn-hero {
            padding: 0.9rem 2.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
            text-decoration: none;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-hero-primary {
            background: #f59e0b;
            color: #fff;
            box-shadow: 0 4px 16px rgba(245,158,11,0.35);
        }
        .btn-hero-primary:hover {
            background: #d97706;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 24px rgba(245,158,11,0.45);
        }
        .btn-hero-outline {
            border: 2px solid rgba(255,255,255,0.3);
            color: #fff;
        }
        .btn-hero-outline:hover {
            border-color: #fff;
            color: #fff;
            transform: translateY(-2px);
        }
        .hero-footer {
            position: absolute;
            bottom: 1.5rem;
            font-size: 0.8rem;
            opacity: 0.4;
        }
        .features {
            padding: 4rem 2rem;
            max-width: 1100px;
            margin: 0 auto;
        }
        .features h2 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1e293b;
        }
        .features .sub {
            text-align: center;
            color: #6c757d;
            margin-bottom: 3rem;
        }
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }
        .feature-card {
            background: #fff;
            border-radius: 1rem;
            padding: 2rem 1.5rem;
            border: 1px solid #e9ecef;
            text-align: center;
            transition: all 0.25s;
        }
        .feature-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .feature-card .icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: #fff;
        }
        .feature-card h5 { font-weight: 700; margin-bottom: 0.5rem; color: #1e293b; }
        .feature-card p { font-size: 0.9rem; color: #6c757d; margin-bottom: 0; }
        .icon-admin { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .icon-hr { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .icon-users { background: linear-gradient(135deg, #06b6d4, #0891b2); }
        .icon-stats { background: linear-gradient(135deg, #10b981, #059669); }
        @media (max-width: 600px) {
            .hero h1 { font-size: 1.75rem; }
            .hero p { font-size: 0.95rem; }
            .btn-hero { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-logo"><i class="bi bi-building"></i></div>
        <h1>مؤسسة الرواد للتنمية</h1>
        <p>منصة إدارية متكاملة لإدارة شؤون الموظفين، والمستفيدين، والمشاريع التنموية. نسعى لتمكين الكوادر البشرية وتحقيق التميز المؤسسي.</p>
        <div class="hero-buttons">
            <a href="{{ route('login') }}" class="btn-hero btn-hero-primary">
                <i class="bi bi-person"></i> تسجيل الدخول
            </a>
        </div>
        <div class="hero-footer">© 2026 مؤسسة الرواد للتنمية. جميع الحقوق محفوظة.</div>
    </section>

    <section class="features">
        <h2>ماذا نقدم؟</h2>
        <p class="sub">منصة متكاملة لإدارة الموارد والمستفيدين</p>
        <div class="features-grid">
            <div class="feature-card">
                <div class="icon icon-admin"><i class="bi bi-grid-3x3-gap"></i></div>
                <h5>إدارة متكاملة</h5>
                <p>إدارة الموظفين، المشاريع، المراكز، والإدارات بكل سهولة.</p>
            </div>
            <div class="feature-card">
                <div class="icon icon-hr"><i class="bi bi-person-workspace"></i></div>
                <h5>الموارد البشرية</h5>
                <p>توثيق بيانات الموظفين، العقود، الرواتب، والإنذارات.</p>
            </div>
            <div class="feature-card">
                <div class="icon icon-users"><i class="bi bi-people"></i></div>
                <h5>إدارة المستفيدين</h5>
                <p>نظام متكامل لمتابعة المستفيدين من الخدمات التنموية.</p>
            </div>
            <div class="feature-card">
                <div class="icon icon-stats"><i class="bi bi-graph-up-arrow"></i></div>
                <h5>تقارير وإحصائيات</h5>
                <p>لوحات بيانات وتحليلات دقيقة لدعم اتخاذ القرارات.</p>
            </div>
        </div>
    </section>
</body>
</html>
