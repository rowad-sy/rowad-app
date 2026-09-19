<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\EventCard;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PortalController extends Controller
{
    /*
     * البوابة الرئيسية بعد تسجيل الدخول: لوغو المؤسسة في الوسط
     * وحوله أزرار دائرية (ملف تعريفي، معرفات، فعاليات، شجرة المسارات).
     */
    public function portal()
    {
        return view('admin.portal.index');
    }

    /*
     * المعرفات الرئيسية للمؤسسة.
     */
    public function identities()
    {
        $identities = [
            ['label' => 'الموقع الرسمي', 'value' => 'https://www.alrowadngo.sy/', 'url' => 'https://www.alrowadngo.sy/', 'icon' => 'bi-globe2', 'color' => '#FF5722'],
            ['label' => 'فيسبوك', 'value' => 'facebook.com/alrowadngosy', 'url' => 'https://www.facebook.com/alrowadngosy', 'icon' => 'bi-facebook', 'color' => '#1877F2'],
            ['label' => 'إنستغرام', 'value' => 'instagram.com/alrowadngosy', 'url' => 'https://www.instagram.com/alrowadngosy/', 'icon' => 'bi-instagram', 'color' => '#E1306C'],
            ['label' => 'إكس (تويتر)', 'value' => 'x.com/alrowadngosy', 'url' => 'https://x.com/alrowadngosy', 'icon' => 'bi-twitter-x', 'color' => '#1DA1F2'],
            ['label' => 'لينكدإن', 'value' => 'linkedin.com/company/alrowadngosy', 'url' => 'https://www.linkedin.com/company/alrowadngosy/', 'icon' => 'bi-linkedin', 'color' => '#0A66C2'],
            ['label' => 'يوتيوب', 'value' => 'youtube.com/@alrowadngosy', 'url' => 'https://www.youtube.com/@alrowadngosy', 'icon' => 'bi-youtube', 'color' => '#FF0000'],
            ['label' => 'تيلغرام', 'value' => 't.me/alrowadngosy', 'url' => 'https://t.me/alrowadngosy', 'icon' => 'bi-telegram', 'color' => '#229ED9'],
            ['label' => 'قناة واتساب', 'value' => 'whatsapp.com/channel/0029Vb6wCqsCHDydvbNSn00Q', 'url' => 'https://whatsapp.com/channel/0029Vb6wCqsCHDydvbNSn00Q', 'icon' => 'bi-whatsapp', 'color' => '#25D366'],
            ['label' => 'البريد الإلكتروني', 'value' => 'info@alrowadngo.sy', 'url' => 'mailto:info@alrowadngo.sy', 'icon' => 'bi-envelope-fill', 'color' => '#FFA000'],
        ];

        return view('admin.identities.index', compact('identities'));
    }

    /*
     * تقويم الفعاليات: يعرض بطاقات الفعاليات (المعتمدة/الموافَق عليها)
     * على شكل تقويم شهري بدون مكتبات خارجية.
     */
    public function calendar(Request $request)
    {
        $year = (int) $request->input('y', now()->year);
        $month = (int) $request->input('m', now()->month);
        if ($year < 2000 || $year > 2100) { $year = now()->year; }
        if ($month < 1 || $month > 12) { $month = now()->month; }

        $first = Carbon::create($year, $month, 1);
        $daysInMonth = $first->daysInMonth;

        // الأسبوع يبدأ السبت: السبت=0 ... الجمعة=6
        $leadingBlanks = ($first->dayOfWeekIso - 6 + 7) % 7;

        $cards = EventCard::with('project')
            ->whereIn('status', ['finalized', 'approved'])
            ->whereYear('event_date', $year)
            ->whereMonth('event_date', $month)
            ->orderBy('event_date')
            ->get();

        $byDay = $cards->groupBy(fn ($c) => $c->event_date->day);

        $cells = [];
        for ($i = 0; $i < $leadingBlanks; $i++) {
            $cells[] = null;
        }
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $cells[] = ['day' => $d, 'events' => $byDay->get($d, collect())];
        }
        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        $weeks = collect($cells)->chunk(7);

        $prev = (clone $first)->subMonth();
        $next = (clone $first)->addMonth();

        $dayNames = ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'];
        $monthLabel = $first->translatedFormat('F Y');
        $today = now();

        return view('admin.events.calendar', compact(
            'weeks', 'dayNames', 'monthLabel', 'year', 'month', 'prev', 'next', 'today', 'cards'
        ));
    }
}
