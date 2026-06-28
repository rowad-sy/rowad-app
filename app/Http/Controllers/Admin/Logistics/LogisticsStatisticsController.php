<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\PurchaseRequest;
use Illuminate\Http\Request;

class LogisticsStatisticsController extends Controller
{
    public function index(Request $request)
    {
        $query = PurchaseRequest::query();

        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        $executedCount = (clone $query)->where('status', 'executed')->count();
        $pendingCount = (clone $query)->where('status', 'pending')->count();
        $rejectedCount = (clone $query)->where('status', 'rejected')->count();
        $totalPendingAmount = (clone $query)->where('status', 'pending')->sum('expected_total_price');

        return view('admin.logistics.statistics.index', compact(
            'executedCount', 'pendingCount', 'rejectedCount', 'totalPendingAmount'
        ));
    }
}
