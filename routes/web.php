<?php

use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Student\StudentController;
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

        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        Route::resource('students', StudentController::class)->except(['show']);

        Route::prefix('hr')->name('hr.')->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::resource('job-positions', JobPositionController::class);
            Route::post('employees/export', [ExportController::class, 'employees'])->name('employees.export');
            Route::post('employees/export-full', [ExportController::class, 'employeesFullExport'])->name('employees.export-full');
            Route::post('employees/import', [ExportController::class, 'importEmployees'])->name('employees.import');
            Route::post('employees/import-full', [ExportController::class, 'importEmployeesFull'])->name('employees.import-full');
        });
    });

});

require __DIR__.'/auth.php';
