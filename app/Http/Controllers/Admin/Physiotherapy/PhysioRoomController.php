<?php

namespace App\Http\Controllers\Admin\Physiotherapy;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\Controller;
use App\Models\Admin\Center;
use App\Models\Admin\Physiotherapy\PhysioRoom;
use Illuminate\Http\Request;

class PhysioRoomController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioRoom,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioRoom,create')->only(['create', 'store']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioRoom,edit')->only(['edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Physiotherapy\PhysioRoom,delete')->only(['destroy']);
    }

    public function index()
    {
        $scope = PermissionHelper::getEffectiveScope(auth()->user(), PhysioRoom::class);

        $rooms = PhysioRoom::with('center')->withCount('patients')
            ->when(! $scope['sees_all'], function ($q) use ($scope) {
                if (! empty($scope['center_ids'])) {
                    $q->whereIn('center_id', $scope['center_ids']);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->orderBy('name')
            ->get();

        return view('admin.physiotherapy.rooms.index', compact('rooms'));
    }

    public function create()
    {
        $centers = Center::orderBy('name')->get();

        return view('admin.physiotherapy.rooms.form', compact('centers'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRoom($request);

        PhysioRoom::create($validated);

        return redirect()->route('admin.physiotherapy.rooms.index')
            ->with('success', 'تم إضافة الغرفة بنجاح');
    }

    public function edit(PhysioRoom $room)
    {
        $centers = Center::orderBy('name')->get();

        return view('admin.physiotherapy.rooms.form', compact('room', 'centers'));
    }

    public function update(Request $request, PhysioRoom $room)
    {
        $validated = $this->validateRoom($request);

        $room->update($validated);

        return redirect()->route('admin.physiotherapy.rooms.index')
            ->with('success', 'تم تحديث الغرفة بنجاح');
    }

    public function destroy(PhysioRoom $room)
    {
        abort_if($room->patients()->exists(), 403, 'لا يمكن حذف غرفة مرتبطة بمرضى.');

        $room->delete();

        return redirect()->route('admin.physiotherapy.rooms.index')
            ->with('success', 'تم حذف الغرفة');
    }

    private function validateRoom(Request $request): array
    {
        return $request->validate([
            'center_id' => 'nullable|exists:centers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'nullable|in:0,1',
        ]);
    }
}