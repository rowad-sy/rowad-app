<?php

namespace App\Http\Controllers\Admin\Physiotherapy;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Physiotherapy\PhysioRoom;
use App\Models\Admin\Physiotherapy\PhysioSession;
use App\Models\User;
use Illuminate\Http\Request;

class PhysioStatisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.physiotherapy.statistics.index,view');
    }

    public function index(Request $request)
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), PhysioPatient::class);

        $centerId = $request->filled('center_id') ? (int) $request->center_id : null;
        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;

        $patientsQuery = PhysioPatient::query();
        $sessionsQuery = PhysioSession::query();

        if (! $scope['sees_all']) {
            if (! empty($scope['center_ids'])) {
                $patientsQuery->whereIn('center_id', $scope['center_ids']);
                $sessionsQuery->whereHas('patient', fn ($q) => $q->whereIn('center_id', $scope['center_ids']));
            } else {
                $patientsQuery->whereRaw('1 = 0');
                $sessionsQuery->whereRaw('1 = 0');
            }
        }

        if ($centerId) {
            $patientsQuery->where('center_id', $centerId);
            $sessionsQuery->whereHas('patient', fn ($q) => $q->where('center_id', $centerId));
        }

        if ($fromDate) {
            $patientsQuery->whereDate('registration_date', '>=', $fromDate);
            $sessionsQuery->whereDate('session_date', '>=', $fromDate);
        }
        if ($toDate) {
            $patientsQuery->whereDate('registration_date', '<=', $toDate);
            $sessionsQuery->whereDate('session_date', '<=', $toDate);
        }

        $totalPatients = (clone $patientsQuery)->count();
        $transferredCount = (clone $patientsQuery)->where('is_transferred', 1)->count();
        $activeCount = $totalPatients - $transferredCount;
        $totalSessions = (clone $sessionsQuery)->count();

        $genderStats = (clone $patientsQuery)
            ->selectRaw('gender, COUNT(*) as total')
            ->groupBy('gender')
            ->pluck('total', 'gender');
        $maleCount = (int) ($genderStats['male'] ?? 0);
        $femaleCount = (int) ($genderStats['female'] ?? 0);

        $patientsPerCenter = (clone $patientsQuery)
            ->with('center')
            ->selectRaw('center_id, COUNT(*) as total')
            ->groupBy('center_id')
            ->get()
            ->map(fn ($row) => ['center' => $row->center?->name ?? '—', 'total' => $row->total]);

        $patientsPerRoom = (clone $patientsQuery)
            ->with('room')
            ->selectRaw('room_id, COUNT(*) as total')
            ->whereNotNull('room_id')
            ->groupBy('room_id')
            ->get()
            ->map(fn ($row) => ['room' => $row->room?->name ?? '—', 'total' => $row->total]);

        $sessionsPerTherapist = (clone $sessionsQuery)
            ->with('therapist')
            ->selectRaw('therapist_id, COUNT(*) as total')
            ->groupBy('therapist_id')
            ->get()
            ->sortByDesc('total')
            ->map(fn ($row) => ['therapist' => $row->therapist?->name ?? '—', 'total' => $row->total])
            ->values();

        $therapists = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);
        $centers = Center::orderBy('name')->get();

        return view('admin.physiotherapy.statistics.index', compact(
            'totalPatients', 'transferredCount', 'activeCount', 'totalSessions',
            'maleCount', 'femaleCount', 'patientsPerCenter', 'patientsPerRoom',
            'sessionsPerTherapist', 'therapists', 'centers',
            'centerId', 'fromDate', 'toDate'
        ));
    }
}