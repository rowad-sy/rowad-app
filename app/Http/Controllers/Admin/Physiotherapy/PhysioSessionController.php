<?php

namespace App\Http\Controllers\Admin\Physiotherapy;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Physiotherapy\PhysioPatient;
use App\Models\Admin\Physiotherapy\PhysioSession;
use Illuminate\Http\Request;

class PhysioSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioSession,edit')->only(['store', 'update']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioSession,delete')->only(['destroy']);
    }

    public function store(Request $request)
    {
        $validated = $this->validateSession($request);

        $patient = PhysioPatient::findOrFail($validated['patient_id']);
        $this->authorizeScope($patient);

        $validated['session_number'] = $validated['session_number']
            ?? (int) $patient->sessions()->max('session_number') + 1;
        $validated['therapist_id'] = $validated['therapist_id']
            ?? $patient->therapist_id
            ?? auth()->id();
        $validated['created_by'] = auth()->id();

        PhysioSession::create($validated);

        return back()->with('success', 'تم تسجيل الجلسة بنجاح');
    }

    public function update(Request $request, PhysioSession $session)
    {
        $this->authorizeScope($session->patient);

        $validated = $this->validateSession($request);

        $session->update($validated);

        return back()->with('success', 'تم تحديث الجلسة بنجاح');
    }

    public function destroy(PhysioSession $session)
    {
        $this->authorizeScope($session->patient);

        $session->delete();

        return back()->with('success', 'تم حذف الجلسة');
    }

    private function validateSession(Request $request): array
    {
        return $request->validate([
            'patient_id' => 'required|exists:physio_patients,id',
            'session_date' => 'required|date',
            'session_number' => 'nullable|integer|min:1',
            'what_done' => 'required|string|max:5000',
            'therapist_id' => 'nullable|exists:users,id',
            'notes' => 'nullable|string|max:2000',
        ]);
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