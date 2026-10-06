<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\AdDesignRequest;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\User;

/*
 * لوحة «روادنا» — صفحة موحدة تظهر فيها البطاقات حسب صلاحيات المستخدم وعهدته
 * في الدورة (مسؤول روادنا / مراسل / ناشر / مراجع / مصمم)، بنفس فلسفة مساحة
 * العمل. الاتصال بالتطبيق عبر نفس الكيانات: اللوحة واجهة تركيز فقط، وروابط
 * الطباعة والتصدير من هنا تخرج بهوية «روادنا» (?theme=rowaduna).
 */
class RowadunaController extends Controller
{
    public const PAGE = 'page:admin.rowaduna.dashboard';

    public function __construct()
    {
        $this->middleware('permission:' . self::PAGE . ',view');
    }

    public function dashboard()
    {
        $user = auth()->user();
        $uid = $user->id;

        $canMedia = PermissionHelper::can($user, 'App\Models\Admin\MediaPlan', 'view');
        $canDesign = PermissionHelper::can($user, 'App\Models\Admin\AdDesignRequest', 'view');

        $outstanding = fn ($status) => MediaPlan::query()
            ->where('status', $status)
            ->when($user->type !== 'super-admin', fn ($q) => $q->where(fn ($s) => $s
                ->where('refer_to_rowaduna_id', $uid)
                ->orWhereHas('activeReferrals', fn ($r) => $r->where('to_user_id', $uid))))
            ->withCount(['events'])
            ->latest('month_date')
            ->get();

        $myCoverageEvents = $canMedia
            ? MediaPlanEvent::with('plan.center', 'reporter')
                ->where('refer_to_reporter_id', $uid)
                ->where('coverage_status', 'assigned')
                ->orderBy('event_date')->orderBy('event_time')
                ->get()
            : collect();

        $myPublishEvents = $canMedia
            ? MediaPlanEvent::with('plan.center', 'reporter')
                ->where('refer_to_publisher_id', $uid)
                ->whereIn('publish_status', ['to_publish', 'rework', 'to_final'])
                ->orderBy('event_date')->orderBy('event_time')
                ->get()
            : collect();

        $myReviewEvents = $canMedia
            ? MediaPlanEvent::with('plan.center', 'reporter', 'publisher')
                ->where('refer_to_reviewer_id', $uid)
                ->where('publish_status', 'to_review')
                ->orderBy('event_date')->orderBy('event_time')
                ->get()
            : collect();

        $adStats = null;
        $myAdRequests = collect();
        $adAwaitingMe = collect();

        if ($canDesign) {
            $myAdRequests = AdDesignRequest::with(['project', 'creator', 'designer'])
                ->where('created_by', $uid)
                ->whereNotIn('status', ['published', 'rejected'])
                ->latest()
                ->get();

            $adAwaitingMe = AdDesignRequest::with(['project', 'creator'])
                ->where('status', 'designing')
                ->where('refer_to_designer_id', $uid)
                ->latest()
                ->get();
        }

        $plansAwaitingAssign = $outstanding('rowaduna_review');
        $plansInProgress = $outstanding('in_progress');
        $adReviewQueue = $canDesign
            ? AdDesignRequest::whereIn('status', ['pm2_review', 'rowaduna_review'])->latest()->limit(8)->get()
            : collect();

        $kpis = [
            ['label' => 'خطط بانتظار الإسناد', 'value' => $plansAwaitingAssign->count(), 'icon' => 'bi-megaphone'],
            ['label' => 'خطط قيد التنفيذ', 'value' => $plansInProgress->count(), 'icon' => 'bi-film'],
            ['label' => 'تغطيات بانتظاري', 'value' => $myCoverageEvents->count(), 'icon' => 'bi-camera-video'],
            ['label' => 'أعمال نشر بانتظاري', 'value' => $myPublishEvents->count(), 'icon' => 'bi-scissors'],
            ['label' => 'مراجعات معاينة بانتظاري', 'value' => $myReviewEvents->count(), 'icon' => 'bi-eye'],
        ];

        if ($canDesign) {
            $kpis[] = ['label' => 'طلبات تصميم نشطة', 'value' => AdDesignRequest::whereNotIn('status', ['published', 'rejected'])->count(), 'icon' => 'bi-brush'];
        }

        return view('admin.rowaduna.dashboard', compact(
            'user', 'canMedia', 'canDesign', 'kpis',
            'plansAwaitingAssign', 'plansInProgress',
            'myCoverageEvents', 'myPublishEvents', 'myReviewEvents',
            'myAdRequests', 'adAwaitingMe', 'adReviewQueue'
        ));
    }
}
