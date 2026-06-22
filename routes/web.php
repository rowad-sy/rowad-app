<?php

use App\Http\Controllers\Admin\BeneficiaryController;
use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\EmployeeStatisticsController;
use App\Http\Controllers\Admin\Student\AttendanceController;
use App\Http\Controllers\Admin\Student\CertificateController;
use App\Http\Controllers\Admin\Student\CourseController;
use App\Http\Controllers\Admin\Student\PeriodController;
use App\Http\Controllers\Admin\Student\StudentController;
use App\Http\Controllers\Admin\Student\StudentStatisticsController;
use App\Http\Controllers\Admin\Hr\JobPositionController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\Tech\TechController;
use App\Http\Controllers\Admin\Tech\TechEquipmentController;
use App\Http\Controllers\Admin\Tech\TechIssueController;
use App\Http\Controllers\Admin\Tech\TechStatisticsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('beneficiary/dashboard', [BeneficiaryController::class, 'dashboard'])->name('beneficiary.dashboard');
        Route::resource('centers', CenterController::class)->except(['show']);
        Route::resource('projects', ProjectController::class)->except(['show']);
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('groups', GroupController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);

        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');

        // Literal routes before wildcard {student}
        Route::get('students/attendance', [AttendanceController::class, 'index'])->name('students.attendance');
        Route::post('students/attendance', [AttendanceController::class, 'store'])->name('students.attendance.store');
        Route::resource('students/courses', CourseController::class)->except(['show'])->names(['index' => 'students.courses.index', 'create' => 'students.courses.create', 'store' => 'students.courses.store', 'edit' => 'students.courses.edit', 'update' => 'students.courses.update', 'destroy' => 'students.courses.destroy']);
        Route::resource('students/periods', PeriodController::class)->except(['show'])->names(['index' => 'students.periods.index', 'create' => 'students.periods.create', 'store' => 'students.periods.store', 'edit' => 'students.periods.edit', 'update' => 'students.periods.update', 'destroy' => 'students.periods.destroy']);
        // Student resource (with wildcard {student})
        Route::get('students/statistics', [StudentStatisticsController::class, 'index'])->name('students.statistics');
        // Certificates
        Route::get('students/certificates', [CertificateController::class, 'index'])->name('students.certificates.index');
        Route::get('students/certificates/designs', [CertificateController::class, 'designs'])->name('students.certificates.designs');
        Route::get('students/certificates/designs/create', [CertificateController::class, 'createDesign'])->name('students.certificates.designer.create');
        Route::post('students/certificates/designs', [CertificateController::class, 'storeDesign'])->name('students.certificates.designs.store');
        Route::get('students/certificates/designs/{id}/edit', [CertificateController::class, 'editDesign'])->name('students.certificates.designer.edit');
        Route::put('students/certificates/designs/{id}', [CertificateController::class, 'updateDesign'])->name('students.certificates.designs.update');
        Route::delete('students/certificates/designs/{id}', [CertificateController::class, 'destroyDesign'])->name('students.certificates.designs.destroy');
        Route::get('students/certificates/issue', [CertificateController::class, 'issue'])->name('students.certificates.issue');
        Route::post('students/certificates/generate', [CertificateController::class, 'generateCertificates'])->name('students.certificates.generate');
        Route::get('students/certificates/{id}/preview', [CertificateController::class, 'preview'])->name('students.certificates.preview');
        Route::get('students/certificates/print-batch', [CertificateController::class, 'printBatch'])->name('students.certificates.print-batch');
        Route::resource('students', StudentController::class);
        Route::post('students/{student}/create-user', [StudentController::class, 'createUser'])->name('students.create-user');

        Route::prefix('hr')->name('hr.')->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::get('employees/statistics', [EmployeeStatisticsController::class, 'index'])->name('employees.statistics');
            Route::resource('job-positions', JobPositionController::class)->except(['show']);
            Route::post('employees/export', [ExportController::class, 'employees'])->name('employees.export');
            Route::post('employees/export-full', [ExportController::class, 'employeesFullExport'])->name('employees.export-full');
            Route::post('employees/import', [ExportController::class, 'importEmployees'])->name('employees.import');
            Route::post('employees/import-full', [ExportController::class, 'importEmployeesFull'])->name('employees.import-full');
        });

        Route::prefix('tech')->name('tech.')->group(function () {
            Route::resource('issues', TechIssueController::class);
            Route::post('issues/{issue}/respond', [TechIssueController::class, 'respond'])->name('issues.respond');
            Route::resource('equipment', TechEquipmentController::class);
            Route::get('statistics', [TechStatisticsController::class, 'index'])->name('statistics');
            Route::get('emails', [TechController::class, 'emails'])->name('emails');
        });
    });

});

// Public certificate verification
Route::get('verify-certificate/{hash}', [CertificateController::class, 'verifyCertificate']);

require __DIR__.'/auth.php';
