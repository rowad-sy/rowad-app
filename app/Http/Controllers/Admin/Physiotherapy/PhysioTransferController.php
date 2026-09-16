<?php

namespace App\Http\Controllers\Admin\Physiotherapy;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use Illuminate\Http\Request;

class PhysioTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:page:admin.physiotherapy.transfers.index,view');
    }

    public function index(Request $request)
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), PhysioPatient::class);

        $query = PhysioPatient::with(['center', 'therapist', 'room'])
            ->where('is_transferred', 1);

        if (! $scope['sees_all']) {
            $query->where(function ($q) use ($scope) {
                if (! empty($scope['center_ids'])) {
                    $q->whereIn('center_id', $scope['center_ids']);
                } else {
                    $q->whereRaw('1 = 0');
                }
            });
        }

        $fromDate = $request->filled('from_date') ? $request->from_date : null;
        $toDate = $request->filled('to_date') ? $request->to_date : null;

        $patients = $query
            ->when($request->filled('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->when($fromDate, fn ($q, $v) => $q->whereDate('transferred_at', '>=', $v))
            ->when($toDate, fn ($q, $v) => $q->whereDate('transferred_at', '<=', $v))
            ->orderBy('transferred_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $centers = Center::orderBy('name')->get();

        return view('admin.physiotherapy.transfers.index', compact(
            'patients', 'centers', 'fromDate', 'toDate'
        ));
    }
}