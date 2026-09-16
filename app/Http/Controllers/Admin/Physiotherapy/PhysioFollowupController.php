<?php

namespace App\Http\Controllers\Admin\Physiotherapy;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\User;
use Illuminate\Http\Request;

class PhysioFollowupController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.physiotherapy.followups.index,view');
    }

    public function index(Request $request)
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), PhysioPatient::class);

        $query = PhysioPatient::with(['center', 'room', 'therapist', 'sessions.therapist'])
            ->withCount('sessions');

        if (! $scope['sees_all']) {
            $query->where(function ($q) use ($scope) {
                if (! empty($scope['center_ids'])) {
                    $q->whereIn('center_id', $scope['center_ids']);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $therapistId = $request->filled('therapist_id') ? (int) $request->therapist_id : null;

        $patients = $query
            ->when($request->filled('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->when($therapistId, fn ($q, $v) => $q->where('therapist_id', $v))
            ->orderBy('name')
            ->get();

        if ($therapistId === null) {
            $patients = $patients->groupBy(fn ($p) => $p->therapist_id ?: 'unassigned');
        } else {
            $patients = ['single' => $patients];
        }

        $centers = Center::orderBy('name')->get();
        $therapists = $this->therapistOptions();
        $selectedTherapist = $therapistId !== null ? User::find($therapistId) : null;

        return view('admin.physiotherapy.followups.index', compact(
            'patients', 'centers', 'therapists', 'selectedTherapist'
        ));
    }

    private function therapistOptions()
    {
        $ids = PhysioPatient::whereNotNull('therapist_id')->pluck('therapist_id')
            ->merge(\App\Models\Admin\Physiotherapy\PhysioSession::whereNotNull('therapist_id')->pluck('therapist_id'))
            ->unique();

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }
}