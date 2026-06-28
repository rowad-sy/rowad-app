<?php

namespace App\Http\Controllers\Admin\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Admin\Logistics\LogisticsSetting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Logistics\LogisticsSetting,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Logistics\LogisticsSetting,edit')->only(['update']);
    }

    public function index()
    {
        $settings = LogisticsSetting::orderBy('key')->get();

        return view('admin.logistics.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'nullable|string|max:1000',
        ]);

        foreach ($validated['settings'] as $item) {
            LogisticsSetting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'] ?? '']
            );
        }

        return redirect()->route('admin.logistics.settings.index')
            ->with('success', 'تم حفظ الإعدادات بنجاح');
    }
}
