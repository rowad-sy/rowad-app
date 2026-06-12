<?php

use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\JobPositionController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('centers', CenterController::class);
        Route::resource('projects', ProjectController::class);
        Route::resource('departments', DepartmentController::class);
        Route::resource('groups', GroupController::class);
        Route::resource('permissions', PermissionController::class);

        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        Route::prefix('hr')->name('hr.')->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::resource('job-positions', JobPositionController::class);
        });
    });

});

require __DIR__.'/auth.php';
