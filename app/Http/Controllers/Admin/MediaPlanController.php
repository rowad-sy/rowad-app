<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Hr\Employee;
use App\Models\Admin\MediaPlan;
use App\Models\Admin\MediaPlanEvent;
use App\Models\Admin\Project;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MediaPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\MediaPlan,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,edit')->only(['edit', 'update', 'storeEvent', 'destroyEvent', 'addComment']);
        $this->middleware('permission:App\Models\Admin\MediaPlan,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $query = MediaPlan::with(['center', 'project', 'creator', 'events'])->withCount('events');

        if ($user->type !== 'super-admin') {
            $employee = Employee::where('user_id', $user->id)->first();
            if ($employee?->center_id) {
                $query->where('center_id', $employee->center_id);
            }
        }

        if ($request->filled('month')) {
            $query->whereMonth('month_date', $request->month);
        }
        if ($request->filled('year')) {
            $query->whereYear('month_date', $request->year);
        }
        if ($request->filled('center_id')) {
            $query->where('center_id', $request->center_id);
        }

        $plans = $query->orderBy('month_date', 'desc')->paginate(15);
        $centers = Center::orderBy('name')->get();

        return view('admin.media-plans.index', compact('plans', 'centers'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.media-plans.form', compact('centers', 'projects', 'users'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePlan($request);
        $events = $this->normalizeEvents($request->input('events', []));

        $this->assertBatchHasNoConflicts($events);

        try {
            DB::beginTransaction();

            $plan = MediaPlan::create([
                'month_date' => $validated['month_date'],
                'center_id' => $validated['center_id'] ?? $this->employeeCenterId(),
                'project_id' => $validated['project_id'] ?? $this->employeeProjectId(),
                'created_by' => auth()->id(),
                'note' => $validated['note'] ?? null,
            ]);

            $this->saveEvents($plan, $events);

            DB::commit();
        } catch (QueryException) {
            DB::rollBack();

            return back()->withInput()->with('error', 'لا يمكن حفظ الخطة — يوجد تعارض في مواعيد الفعاليات (نفس التاريخ والساعة).');
        }

        return redirect()->route('admin.media-plans.show', $plan)
            ->with('success', 'تم إنشاء الخطة الإعلامية بنجاح');
    }

    public function show(MediaPlan $plan)
    {
        $plan->load(['center', 'project', 'creator', 'events.responsible', 'events.comments.user']);
        $users = User::orderBy('name')->get();

        return view('admin.media-plans.show', compact('plan', 'users'));
    }

    public function edit(MediaPlan $plan)
    {
        $plan->load('events');
        $centers = Center::orderBy('name')->get();
        $projects = Project::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('admin.media-plans.form', compact('plan', 'centers', 'projects', 'users'));
    }

    public function update(Request $request, MediaPlan $plan)
    {
        $validated = $this->validatePlan($request);
        $events = $this->normalizeEvents($request->input('events', []));

        $this->assertBatchHasNoConflicts($events);

        try {
            DB::beginTransaction();

            $plan->update([
                'month_date' => $validated['month_date'],
                'center_id' => $validated['center_id'] ?? $plan->center_id ?? $this->employeeCenterId(),
                'project_id' => $validated['project_id'] ?? $plan->project_id ?? $this->employeeProjectId(),
                'note' => $validated['note'] ?? null,
            ]);

            $plan->events()->delete();
            $this->saveEvents($plan, $events);

            DB::commit();
        } catch (QueryException) {
            DB::rollBack();

            return back()->withInput()->with('error', 'لا يمكن تحديث الخطة — يوجد تعارض في مواعيد الفعاليات (نفس التاريخ والساعة).');
        }

        return redirect()->route('admin.media-plans.show', $plan)
            ->with('success', 'تم تحديث الخطة الإعلامية بنجاح');
    }

    public function destroy(MediaPlan $plan)
    {
        $plan->delete();

        return redirect()->route('admin.media-plans.index')
            ->with('success', 'تم حذف الخطة الإعلامية بنجاح');
    }

    public function storeEvent(Request $request, MediaPlan $plan)
    {
        $validated = $this->validateEvent($request);

        $exists = $plan->events()
            ->where('event_date', $validated['event_date'])
            ->where('event_time', $validated['event_time'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'يوجد تعارض — توجد فعالية أخرى بنفس التاريخ والساعة في هذه الخطة.');
        }

        $plan->events()->create([
            'event_date' => $validated['event_date'],
            'office' => $validated['office'] ?? null,
            'event_name' => $validated['event_name'],
            'day' => $validated['day'] ?? Carbon::parse($validated['event_date'])->locale('ar')->translatedFormat('l'),
            'event_time' => $validated['event_time'],
            'location' => $validated['location'] ?? null,
            'responsible_user_id' => $validated['responsible_user_id'] ?? null,
            'summary' => $validated['summary'] ?? null,
            'coverage_type' => $validated['coverage_type'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'تمت إضافة الفعالية بنجاح');
    }

    public function destroyEvent(MediaPlanEvent $event)
    {
        $event->delete();

        return back()->with('success', 'تم حذف الفعالية بنجاح');
    }

    public function addComment(Request $request, MediaPlanEvent $event)
    {
        $validated = $request->validate([
            'comment' => 'required|string|max:2000',
        ]);

        $event->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'تم إضافة التعليق على الموعد بنجاح');
    }

    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'month_date' => 'required|date',
            'center_id' => 'nullable|exists:centers,id',
            'project_id' => 'nullable|exists:projects,id',
            'note' => 'nullable|string|max:2000',
        ]);
    }

    private function validateEvent(Request $request): array
    {
        return $request->validate([
            'event_date' => 'required|date',
            'office' => 'nullable|string|max:255',
            'event_name' => 'required|string|max:255',
            'day' => 'nullable|string|max:20',
            'event_time' => 'required|date_format:H:i',
            'location' => 'nullable|string|max:255',
            'responsible_user_id' => 'nullable|exists:users,id',
            'summary' => 'nullable|string|max:2000',
            'coverage_type' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    private function normalizeEvents(array $events): array
    {
        return array_values(array_filter($events, function ($ev) {
            return isset($ev['event_date']) && $ev['event_date'] !== '' && isset($ev['event_time']) && $ev['event_time'] !== '';
        }));
    }

    private function assertBatchHasNoConflicts(array $events): void
    {
        $seen = [];
        $messages = [];

        foreach ($events as $index => $ev) {
            $key = $ev['event_date'] . '|' . $ev['event_time'];

            if (isset($seen[$key])) {
                $messages[] = 'الفعالية "' . ($ev['event_name'] ?? '') . '" في السطر ' . ($index + 1)
                    . ' تتعارض مع السطر ' . ($seen[$key] + 1) . ' — نفس التاريخ والساعة.';
            } else {
                $seen[$key] = $index;
            }
        }

        if ($messages) {
            throw ValidationException::withMessages(['events' => $messages]);
        }
    }

    private function saveEvents(MediaPlan $plan, array $events): void
    {
        foreach ($events as $ev) {
            $plan->events()->create([
                'event_date' => $ev['event_date'],
                'office' => $ev['office'] ?? null,
                'event_name' => $ev['event_name'],
                'day' => $ev['day'] ?? Carbon::parse($ev['event_date'])->locale('ar')->translatedFormat('l'),
                'event_time' => $ev['event_time'],
                'location' => $ev['location'] ?? null,
                'responsible_user_id' => $ev['responsible_user_id'] ?? null,
                'summary' => $ev['summary'] ?? null,
                'coverage_type' => $ev['coverage_type'] ?? null,
                'notes' => $ev['notes'] ?? null,
            ]);
        }
    }

    private function employeeCenterId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('center_id');
    }

    private function employeeProjectId(): ?int
    {
        return Employee::where('user_id', auth()->id())->value('project_id');
    }
}