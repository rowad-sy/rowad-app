<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BeneficiaryController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        return view('admin.beneficiary.dashboard', compact('user'));
    }
}
