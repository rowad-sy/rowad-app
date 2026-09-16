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

class PhysioPatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioPatient,view')->only(['index', 'show']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioPatient,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioPatient,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioPatient,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), PhysioPatient::class);

        $query = PhysioPatient::with(['center', 'therapist', 'room'])
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

        $patients = $query
            ->when($request->filled('center_id'), fn ($q, $v) => $q->where('center_id', $v))
            ->when($request->filled('gender'), fn ($q, $v) => $q->where('gender', $v))
            ->when($request->filled('therapist_id'), fn ($q, $v) => $q->where('therapist_id', $v))
            ->when($request->filled('room_id'), fn ($q, $v) => $q->where('room_id', $v))
            ->when($request->filled('transferred'), fn ($q, $v) => $q->where('is_transferred', (int) $v))
            ->when($request->filled('search'), function ($q, $v) {
                $q->where(function ($s) use ($v) {
                    $s->where('name', 'like', "%{$v}%")
                        ->orWhere('phone', 'like', "%{$v}%");
                });
            })
            ->orderBy('registration_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        $centers = Center::orderBy('name')->get();
        $rooms = PhysioRoom::orderBy('name')->get();
        $therapists = $this->therapistOptions();

        return view('admin.physiotherapy.patients.index', compact(
            'patients', 'centers', 'rooms', 'therapists'
        ));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();
        $rooms = PhysioRoom::orderBy('name')->get();
        $therapists = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);

        return view('admin.physiotherapy.patients.form', compact('centers', 'rooms', 'therapists'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePatient($request);
        $validated = $this->normalizeTransfer($validated);
        $validated['created_by'] = auth()->id();

        $patient = PhysioPatient::create($validated);

        return redirect()->route('admin.physiotherapy.patients.show', $patient)
            ->with('success', 'تم تسجيل المريض بنجاح');
    }

    public function show(PhysioPatient $patient)
    {
        $this->authorizeScope($patient);

        $patient->load(['center', 'therapist', 'room', 'creator', 'sessions.therapist']);
        $therapists = $this->therapistOptions();

        return view('admin.physiotherapy.patients.show', compact('patient', 'therapists'));
    }

    public function edit(PhysioPatient $patient)
    {
        $this->authorizeScope($patient);

        $centers = Center::orderBy('name')->get();
        $rooms = PhysioRoom::orderBy('name')->get();
        $therapists = User::where('type', 'employee')->orderBy('name')->get(['id', 'name']);

        return view('admin.physiotherapy.patients.form', compact('patient', 'centers', 'rooms', 'therapists'));
    }

    public function update(Request $request, PhysioPatient $patient)
    {
        $this->authorizeScope($patient);

        $validated = $this->validatePatient($request);
        $validated = $this->normalizeTransfer($validated);

        $patient->update($validated);

        return redirect()->route('admin.physiotherapy.patients.show', $patient)
            ->with('success', 'تم تحديث بيانات المريض بنجاح');
    }

    public function destroy(PhysioPatient $patient)
    {
        $this->authorizeScope($patient);

        $patient->delete();

        return redirect()->route('admin.physiotherapy.patients.index')
            ->with('success', 'تم حذف المريض');
    }

    private function validatePatient(Request $request): array
    {
        return $request->validate([
            'center_id' => 'nullable|exists:centers,id',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'birth_date' => 'nullable|date|before_or_equal:today',
            'medical_history' => 'nullable|string|max:5000',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'is_transferred' => 'nullable|in:0,1',
            'transferred_at' => 'nullable|required_if:is_transferred,1|date',
            'registration_date' => 'required|date',
            'therapist_id' => 'nullable|exists:users,id',
            'room_id' => 'nullable|exists:physio_rooms,id',
            'notes' => 'nullable|string|max:5000',
        ]);
    }

    private function normalizeTransfer(array $validated): array
    {
        $validated['is_transferred'] = ($validated['is_transferred'] ?? 0) ? 1 : 0;
        if (! $validated['is_transferred']) {
            $validated['transferred_at'] = null;
        }

        return $validated;
    }

    private function therapistOptions()
    {
        $ids = PhysioPatient::whereNotNull('therapist_id')->pluck('therapist_id')
            ->merge(PhysioSession::whereNotNull('therapist_id')->pluck('therapist_id'))
            ->unique();

        return User::whereIn('id', $ids)->orderBy('name')->get(['id', 'name']);
    }

    private function authorizeScope(PhysioPatient $patient): void
    {
        $user = auth()->user();

        if ($user->type === 'super-admin') {
            return;
        }

        $scope = PermissionHelper::getEffectiveScope($user, PhysioPatient::class);

        if ($scope['sees_all']) {
            return;
        }

        $inScope = $patient->center_id !== null && in_array($patient->center_id, $scope['center_ids'], true);
        if (! $inScope) {
            abort(403, 'هذا المريض خارج نطاق صلاحياتك');
        }
    }
}