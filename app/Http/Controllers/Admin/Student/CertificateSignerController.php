<?php

namespace App\Http\Controllers\Admin\Student;

use App\Http\Controllers\Controller;
use App\Models\Admin\Student\CertificateSigner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateSignerController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\Admin\Student\Certificate,view')->only(['index']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,create')->only(['create', 'store', 'edit', 'update']);
        $this->middleware('permission:App\Models\Admin\Student\Certificate,delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $role = $request->input('role');
        $perPage = (int) $request->input('per_page', 15);

        $signers = CertificateSigner::query()
            ->when($search, fn($q, $v) => $q->where('name_ar', 'like', "%{$v}%"))
            ->when($role && isset(CertificateSigner::ROLES[$role]), fn($q, $v) => $q->where('role', $v))
            ->orderBy('role')
            ->orderBy('name_ar')
            ->paginate($perPage)
            ->appends($request->only(['search', 'role', 'per_page']));

        return view('admin.students.certificates.signers.index', compact('signers', 'search', 'role'));
    }

    public function create()
    {
        return view('admin.students.certificates.signers.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:200',
            'role' => 'required|in:' . implode(',', array_keys(CertificateSigner::ROLES)),
            'signature' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $data = ['name_ar' => $validated['name_ar'], 'role' => $validated['role']];

        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('certificates/signatures', 'public');
        }

        CertificateSigner::create($data);

        return redirect()->route('admin.students.certificates.signers.index')
            ->with('success', 'تم إضافة الموقع بنجاح');
    }

    public function edit(CertificateSigner $signer)
    {
        return view('admin.students.certificates.signers.form', compact('signer'));
    }

    public function update(Request $request, CertificateSigner $signer)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:200',
            'role' => 'required|in:' . implode(',', array_keys(CertificateSigner::ROLES)),
            'signature' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $signer->update([
            'name_ar' => $validated['name_ar'],
            'role' => $validated['role'],
        ]);

        if ($request->hasFile('signature')) {
            if ($signer->signature_path) {
                Storage::disk('public')->delete($signer->signature_path);
            }
            $signer->update(['signature_path' => $request->file('signature')->store('certificates/signatures', 'public')]);
        }

        return redirect()->route('admin.students.certificates.signers.index')
            ->with('success', 'تم تحديث الموقع بنجاح');
    }

    public function destroy(CertificateSigner $signer)
    {
        if ($signer->isUsedInSets()) {
            return redirect()->route('admin.students.certificates.signers.index')
                ->with('error', 'لا يمكن حذف هذا الموقع لأنه مستخدم في مجموعة توقيعات. احذف استخدامه أولاً.');
        }

        if ($signer->signature_path) {
            Storage::disk('public')->delete($signer->signature_path);
        }
        $signer->delete();

        return redirect()->route('admin.students.certificates.signers.index')
            ->with('success', 'تم حذف الموقع');
    }
}
