<?php

namespace App\Http\Controllers\Admin\Tech;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TechController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:App\Models\User,view');
    }

    public function emails(Request $request)
    {
        $search = $request->input('search');

        $users = User::when($search, function ($q, $search) {
            return $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('official_email', 'like', "%{$search}%");
            });
        })->orderBy('name')->paginate(20);

        return view('admin.tech.emails', compact('users', 'search'));
    }
}
