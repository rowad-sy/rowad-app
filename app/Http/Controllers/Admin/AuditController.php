<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\AuditLog,view')->only(['index', 'history']);
    }

    public function index(Request $request)
    {
        $query = AuditLog::with('changes', 'user');

        if ($request->filled('model_name')) {
            $query->where('model_name', $request->model_name);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('model_name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        $modelNames = AuditLog::distinct()->pluck('model_name')->sort()->values();
        $events = AuditLog::distinct()->pluck('event')->sort()->values();
        $users = \App\Models\User::orderBy('name')->get();

        return view('admin.audit-logs.index', compact('logs', 'modelNames', 'events', 'users'));
    }

    public function history(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
            'model_id' => 'required|integer',
        ]);

        $logs = AuditLog::with('changes', 'user')
            ->where('model', $request->model)
            ->where('model_id', $request->model_id)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.audit-logs.partials.history', compact('logs'));
    }
}
