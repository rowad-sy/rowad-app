<?php

use App\Http\Controllers\Admin\BeneficiaryController;
use App\Http\Controllers\Admin\CenterController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\GroupController;
use App\Http\Controllers\Admin\ExportController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\Hr\EmployeeController;
use App\Http\Controllers\Admin\Hr\EmployeeStatisticsController;
use App\Http\Controllers\Admin\Hr\HrAttendanceController;
use App\Http\Controllers\Admin\Hr\LeaveApprovalController;
use App\Http\Controllers\Admin\Hr\LeavePolicyController;
use App\Http\Controllers\Admin\Hr\LeaveRequestController;
use App\Http\Controllers\Admin\Hr\TimesheetController;
use App\Http\Controllers\Admin\Student\AttendanceController;
use App\Http\Controllers\Admin\Student\CertificateController;
use App\Http\Controllers\Admin\Student\CertificateSignerController;
use App\Http\Controllers\Admin\Student\CertificateSignatorySetController;
use App\Http\Controllers\Admin\Student\CourseController;
use App\Http\Controllers\Admin\Student\AcademicLevelController;
use App\Http\Controllers\Admin\Student\TrainingPlanController;
use App\Http\Controllers\Admin\Student\StudentGradeController;
use App\Http\Controllers\Admin\Student\PeriodController;
use App\Http\Controllers\Admin\Student\StudentController;
use App\Http\Controllers\Admin\Student\StudentExportController;
use App\Http\Controllers\Admin\Student\StudentStatisticsController;
use App\Http\Controllers\Admin\Hr\JobPositionController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectHubController;
use App\Http\Controllers\Admin\ProjectManagerController;
use App\Http\Controllers\Admin\ProjectPathController;
use App\Http\Controllers\Admin\ProjectsManagerController;
use App\Http\Controllers\Admin\ProjectOfficerController;
use App\Http\Controllers\Admin\PortalController;
use App\Http\Controllers\Admin\EventCardController;
use App\Http\Controllers\Admin\ProjectTaskController;
use App\Http\Controllers\Admin\ProjectActivityController;
use App\Http\Controllers\Admin\Tech\TechController;
use App\Http\Controllers\Admin\Tech\TechEquipmentController;
use App\Http\Controllers\Admin\Tech\TechIssueController;
use App\Http\Controllers\Admin\Tech\TechStatisticsController;
use App\Http\Controllers\Admin\Logistics\SettingsController;
use App\Http\Controllers\Admin\Logistics\ApprovalRuleController;
use App\Http\Controllers\Admin\Logistics\PurchaseRequestController;
use App\Http\Controllers\Admin\Logistics\PurchaseRequestApprovalController;
use App\Http\Controllers\Admin\Logistics\WarehouseController;
use App\Http\Controllers\Admin\Logistics\WarehouseItemController;
use App\Http\Controllers\Admin\Logistics\AssetController;
use App\Http\Controllers\Admin\Logistics\LogisticsStatisticsController;
use App\Http\Controllers\Admin\Logistics\LogisticsExportController;
use App\Http\Controllers\Admin\MediaPlanController;
use App\Http\Controllers\Admin\MovementPlanController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\ActivationResendController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->type === 'student') {
            $student = \App\Models\Admin\Student\Student::where('user_id', $user->id)->first();
            if ($student) {
                return redirect()->route('admin.students.show', $student);
            }
        } elseif ($user->type === 'beneficiary') {
            return redirect()->route('admin.beneficiary.dashboard');
        } else {
            return redirect()->route('admin.portal');
        }
    }
    // الصفحة الرئيسية = تسجيل الدخول فقط مع لوغو المؤسسة
    return redirect()->route('login');
})->name('home');

// Accessible to all authenticated users (profile & password change)
// خاصة بالمستخدمين النشطين وغير النشطين - لا تتطلب التحقق من البريد
Route::middleware('auth')->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->name('profile.password');
        Route::get('password/change', [ProfileController::class, 'forceChange'])->name('password.change');
        Route::put('password/change', [ProfileController::class, 'forceUpdate'])->name('password.update');
    });

    // تفعيل الحساب من رابط البريد الإلكتروني
    Route::get('activate/{user}', ActivationController::class)
        ->middleware('signed')
        ->name('activation.verify');

    // إعادة إرسال بريد التفعيل للمستخدم غير النشط
    Route::post('activation/resend', ActivationResendController::class)
        ->name('activation.resend');

});

Route::middleware(['auth', 'verified', 'active', 'password_changed'])->group(function () {

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [HomeController::class, 'index'])->name('home');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('beneficiary/dashboard', [BeneficiaryController::class, 'dashboard'])->name('beneficiary.dashboard');
        Route::get('project-manager', [ProjectManagerController::class, 'dashboard'])->name('project-manager.dashboard');
        Route::get('projects-manager', [ProjectsManagerController::class, 'dashboard'])->name('projects-manager.dashboard');
        Route::get('project-officer', [ProjectOfficerController::class, 'dashboard'])->name('project-officer.dashboard');
        Route::resource('centers', CenterController::class)->except(['show']);
        Route::prefix('projects')->name('projects.')->group(function () {
            Route::get('tasks', [ProjectTaskController::class, 'index'])->name('tasks.index');
            Route::get('tasks/create', [ProjectTaskController::class, 'create'])->name('tasks.create');
            Route::post('tasks', [ProjectTaskController::class, 'store'])->name('tasks.store');
            Route::get('tasks/{task}', [ProjectTaskController::class, 'show'])->name('tasks.show');
            Route::get('tasks/{task}/edit', [ProjectTaskController::class, 'edit'])->name('tasks.edit');
            Route::put('tasks/{task}', [ProjectTaskController::class, 'update'])->name('tasks.update');
            Route::delete('tasks/{task}', [ProjectTaskController::class, 'destroy'])->name('tasks.destroy');
            Route::post('tasks/{task}/update-status', [ProjectTaskController::class, 'updateStatus'])->name('tasks.update-status');
            Route::get('calendar', [ProjectTaskController::class, 'calendar'])->name('calendar');
            Route::get('statistics', [ProjectTaskController::class, 'statistics'])->name('statistics');
        });
        Route::prefix('media-plans')->name('media-plans.')->group(function () {
            Route::get('/', [MediaPlanController::class, 'index'])->name('index');
            Route::get('create', [MediaPlanController::class, 'create'])->name('create');
            Route::post('/', [MediaPlanController::class, 'store'])->name('store');
            Route::get('help', [MediaPlanController::class, 'help'])->name('help');
            Route::get('{plan}', [MediaPlanController::class, 'show'])->name('show');
            Route::get('{plan}/edit', [MediaPlanController::class, 'edit'])->name('edit');
            Route::put('{plan}', [MediaPlanController::class, 'update'])->name('update');
            Route::delete('{plan}', [MediaPlanController::class, 'destroy'])->name('destroy');
            Route::post('{plan}/events', [MediaPlanController::class, 'storeEvent'])->name('events.store');
            Route::delete('events/{event}', [MediaPlanController::class, 'destroyEvent'])->name('events.destroy');
            Route::post('events/{event}/comment', [MediaPlanController::class, 'addComment'])->name('events.comment');
            Route::post('events/{event}/mark', [MediaPlanController::class, 'markEvent'])->name('events.mark');
            Route::post('{plan}/direct-manager-decide', [MediaPlanController::class, 'directManagerDecide'])->name('direct-manager-decide');
            Route::post('{plan}/pm2-decide', [MediaPlanController::class, 'pm2Decide'])->name('pm2-decide');
            Route::post('{plan}/media-manager-decide', [MediaPlanController::class, 'mediaManagerDecide'])->name('media-manager-decide');
            Route::post('{plan}/finalize', [MediaPlanController::class, 'finalize'])->name('finalize');
            Route::post('{plan}/refer', [MediaPlanController::class, 'refer'])->name('refer');
        });
        Route::prefix('movement-plans')->name('movement-plans.')->group(function () {
            Route::get('/', [MovementPlanController::class, 'index'])->name('index');
            Route::get('create', [MovementPlanController::class, 'create'])->name('create');
            Route::post('/', [MovementPlanController::class, 'store'])->name('store');
            Route::get('help', [MovementPlanController::class, 'help'])->name('help');
            Route::get('{movement_plan}', [MovementPlanController::class, 'show'])->name('show');
            Route::post('{movement_plan}/approve', [MovementPlanController::class, 'approve'])->name('approve');
            Route::post('{movement_plan}/reject', [MovementPlanController::class, 'reject'])->name('reject');
            Route::post('{movement_plan}/assign', [MovementPlanController::class, 'assign'])->name('assign');
            Route::post('{movement_plan}/complete', [MovementPlanController::class, 'complete'])->name('complete');
            Route::post('{movement_plan}/refer', [MovementPlanController::class, 'refer'])->name('refer');
            Route::delete('{movement_plan}', [MovementPlanController::class, 'destroy'])->name('destroy');
        });
        Route::resource('projects', ProjectController::class)->except(['show']);

        // صفحة المشروع المخصصة (Hub)
        Route::get('projects/{project}/overview', [ProjectHubController::class, 'show'])->name('projects.overview');

        // المسارات
        Route::prefix('paths')->name('paths.')->group(function () {
            Route::get('tree', [ProjectPathController::class, 'tree'])->name('tree');
            Route::get('export/excel', [ProjectPathController::class, 'exportExcel'])->name('export.excel');
            Route::get('export/pdf', [ProjectPathController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/', [ProjectPathController::class, 'index'])->name('index');
            Route::get('create', [ProjectPathController::class, 'create'])->name('create');
            Route::post('/', [ProjectPathController::class, 'store'])->name('store');
            Route::get('{path}/edit', [ProjectPathController::class, 'edit'])->name('edit');
            Route::put('{path}', [ProjectPathController::class, 'update'])->name('update');
            Route::delete('{path}', [ProjectPathController::class, 'destroy'])->name('destroy');
        });

        // بطاقات الفعاليات
        Route::prefix('event-cards')->name('event-cards.')->group(function () {
            Route::get('/', [EventCardController::class, 'index'])->name('index');
            Route::get('create', [EventCardController::class, 'create'])->name('create');
            Route::post('/', [EventCardController::class, 'store'])->name('store');
            Route::get('{eventCard}', [EventCardController::class, 'show'])->name('show');
            Route::get('{eventCard}/edit', [EventCardController::class, 'edit'])->name('edit');
            Route::put('{eventCard}', [EventCardController::class, 'update'])->name('update');
            Route::delete('{eventCard}', [EventCardController::class, 'destroy'])->name('destroy');
            Route::post('{eventCard}/approve', [EventCardController::class, 'approve'])->name('approve');
            Route::post('{eventCard}/reject', [EventCardController::class, 'reject'])->name('reject');
            Route::post('{eventCard}/finalize', [EventCardController::class, 'finalize'])->name('finalize');
            Route::post('{eventCard}/refer', [EventCardController::class, 'refer'])->name('refer');
        });

        // البوابة: الصفحة الرئيسية بعد تسجيل الدخول + المعرفات + تقويم الفعاليات
        Route::get('portal', [PortalController::class, 'portal'])->name('portal');
        Route::get('identities', [PortalController::class, 'identities'])->name('identities');
        Route::get('events-calendar', [PortalController::class, 'calendar'])->name('events-calendar');
        Route::prefix('project-docs')->name('project-docs.')->group(function () {
            Route::resource('templates', \App\Http\Controllers\Admin\ProjectDocs\AnnexTemplateController::class)->except(['show']);
            Route::get('documents', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'index'])->name('documents.index');
            Route::get('documents/create', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'create'])->name('documents.create');
            Route::post('documents', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'store'])->name('documents.store');
            Route::get('documents/help', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'help'])->name('documents.help');
            Route::get('documents/{document}', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'show'])->name('documents.show');
            Route::get('documents/{document}/edit', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'edit'])->name('documents.edit');
            Route::put('documents/{document}', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'update'])->name('documents.update');
            Route::post('documents/{document}/submit', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'submit'])->name('documents.submit');
            Route::post('documents/{document}/reopen', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'reopen'])->name('documents.reopen');
            Route::post('documents/{document}/signoff', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'signoff'])->name('documents.signoff');
            Route::post('documents/{document}/duplicate', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'duplicate'])->name('documents.duplicate');
            Route::get('documents/{document}/print', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'printDocument'])->name('documents.print');
            Route::delete('documents/{document}', [\App\Http\Controllers\Admin\ProjectDocs\AnnexDocumentController::class, 'destroy'])->name('documents.destroy');
        });
        Route::prefix('monthly-reports')->name('monthly-reports.')->group(function () {
            Route::resource('templates', \App\Http\Controllers\Admin\MonthlyReports\MonthlyReportTemplateController::class)->except(['show']);
            Route::get('/', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'index'])->name('index');
            Route::get('create', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'create'])->name('create');
            Route::post('/', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'store'])->name('store');
            Route::get('{report}', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'show'])->name('show');
            Route::get('{report}/edit', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'edit'])->name('edit');
            Route::put('{report}', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'update'])->name('update');
            Route::post('{report}/submit', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'submit'])->name('submit');
            Route::post('{report}/reopen', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'reopen'])->name('reopen');
            Route::post('{report}/signoff', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'signoff'])->name('signoff');
            Route::post('{report}/duplicate', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'duplicate'])->name('duplicate');
            Route::get('{report}/print', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'printDocument'])->name('print');
            Route::delete('{report}', [\App\Http\Controllers\Admin\MonthlyReports\MonthlyReportController::class, 'destroy'])->name('destroy');
        });
        Route::resource('project-activities', ProjectActivityController::class)->except(['show']);
        Route::prefix('physiotherapy')->name('physiotherapy.')->group(function () {
            Route::resource('rooms', \App\Http\Controllers\Admin\Physiotherapy\PhysioRoomController::class)->except(['show']);
            Route::resource('patients', \App\Http\Controllers\Admin\Physiotherapy\PhysioPatientController::class);
            Route::post('sessions', [\App\Http\Controllers\Admin\Physiotherapy\PhysioSessionController::class, 'store'])->name('sessions.store');
            Route::put('sessions/{session}', [\App\Http\Controllers\Admin\Physiotherapy\PhysioSessionController::class, 'update'])->name('sessions.update');
            Route::delete('sessions/{session}', [\App\Http\Controllers\Admin\Physiotherapy\PhysioSessionController::class, 'destroy'])->name('sessions.destroy');
            Route::get('followups', [\App\Http\Controllers\Admin\Physiotherapy\PhysioFollowupController::class, 'index'])->name('followups.index');
            Route::get('transfers', [\App\Http\Controllers\Admin\Physiotherapy\PhysioTransferController::class, 'index'])->name('transfers.index');
            Route::get('statistics', [\App\Http\Controllers\Admin\Physiotherapy\PhysioStatisticsController::class, 'index'])->name('statistics.index');
        });
        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('groups', GroupController::class)->except(['show']);
        Route::resource('cohorts', CohortController::class)->except(['show']);
        Route::resource('permissions', PermissionController::class)->except(['show']);

        // Audit Log
        Route::get('audit-logs', [AuditController::class, 'index'])->name('audit-logs.index');
        Route::get('audit-logs/history', [AuditController::class, 'history'])->name('audit-logs.history');

        Route::get('users/export', [UserController::class, 'export'])->name('users.export');
        Route::post('users/import', [UserController::class, 'import'])->name('users.import');
        Route::resource('users', UserController::class)->except(['show']);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Literal routes before wildcard {student}
        Route::get('students/attendance', [AttendanceController::class, 'index'])->name('students.attendance');
        Route::post('students/attendance', [AttendanceController::class, 'store'])->name('students.attendance.store');
        Route::get('students/courses/help', [CourseController::class, 'help'])->name('students.courses.help');
        Route::resource('students/courses', CourseController::class)->except(['show'])->names(['index' => 'students.courses.index', 'create' => 'students.courses.create', 'store' => 'students.courses.store', 'edit' => 'students.courses.edit', 'update' => 'students.courses.update', 'destroy' => 'students.courses.destroy']);
        Route::get('students/enrollments/{enrollment}/grades', [StudentGradeController::class, 'edit'])->name('students.enrollments.grades.edit');
        Route::put('students/enrollments/{enrollment}/grades', [StudentGradeController::class, 'update'])->name('students.enrollments.grades.update');
        Route::resource('students/periods', PeriodController::class)->except(['show'])->names(['index' => 'students.periods.index', 'create' => 'students.periods.create', 'store' => 'students.periods.store', 'edit' => 'students.periods.edit', 'update' => 'students.periods.update', 'destroy' => 'students.periods.destroy']);
        Route::get('students/levels', [AcademicLevelController::class, 'index'])->name('students.levels.index');
        Route::get('students/levels/create', [AcademicLevelController::class, 'create'])->name('students.levels.create');
        Route::post('students/levels', [AcademicLevelController::class, 'store'])->name('students.levels.store');
        Route::get('students/levels/{level}/edit', [AcademicLevelController::class, 'edit'])->name('students.levels.edit');
        Route::put('students/levels/{level}', [AcademicLevelController::class, 'update'])->name('students.levels.update');
        Route::get('students/levels/{level}', [AcademicLevelController::class, 'show'])->name('students.levels.show');
        Route::get('students/training-plans', [TrainingPlanController::class, 'index'])->name('students.training-plans.index');
        Route::get('students/training-plans/create', [TrainingPlanController::class, 'create'])->name('students.training-plans.create');
        Route::post('students/training-plans', [TrainingPlanController::class, 'store'])->name('students.training-plans.store');
        Route::get('students/training-plans/{plan}/edit', [TrainingPlanController::class, 'edit'])->name('students.training-plans.edit');
        Route::put('students/training-plans/{plan}', [TrainingPlanController::class, 'update'])->name('students.training-plans.update');
        Route::get('students/training-plans/{plan}', [TrainingPlanController::class, 'show'])->name('students.training-plans.show');
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
        Route::post('students/certificates/{id}/cancel', [CertificateController::class, 'cancel'])->name('students.certificates.cancel');
        Route::get('students/certificates/print-batch', [CertificateController::class, 'printBatch'])->name('students.certificates.print-batch');
        // Certificate signers & signatory sets (temporary standalone data until HR tables are active)
        Route::resource('students/certificates/signers', CertificateSignerController::class)->except(['show'])->parameters(['signers' => 'signer'])->names(['index' => 'students.certificates.signers.index', 'create' => 'students.certificates.signers.create', 'store' => 'students.certificates.signers.store', 'edit' => 'students.certificates.signers.edit', 'update' => 'students.certificates.signers.update', 'destroy' => 'students.certificates.signers.destroy']);
        Route::resource('students/certificates/signatory-sets', CertificateSignatorySetController::class)->except(['show'])->parameters(['signatory-sets' => 'set'])->names(['index' => 'students.certificates.signatory-sets.index', 'create' => 'students.certificates.signatory-sets.create', 'store' => 'students.certificates.signatory-sets.store', 'edit' => 'students.certificates.signatory-sets.edit', 'update' => 'students.certificates.signatory-sets.update', 'destroy' => 'students.certificates.signatory-sets.destroy']);
        Route::post('students/check-identity', [StudentController::class, 'checkIdentity'])->name('students.check-identity');
        Route::post('students/{student}/add-to-projects', [StudentController::class, 'addToProjects'])->name('students.add-to-projects');
        Route::resource('students', StudentController::class);
        Route::post('students/{student}/create-user', [StudentController::class, 'createUser'])->name('students.create-user');
        Route::post('students/export', [StudentExportController::class, 'export'])->name('students.export');
        Route::post('students/export-full', [StudentExportController::class, 'exportFull'])->name('students.export-full');
        Route::post('students/import', [StudentExportController::class, 'import'])->name('students.import');
        Route::post('students/import-full', [StudentExportController::class, 'importFull'])->name('students.import-full');

        Route::prefix('hr')->name('hr.')->group(function () {
            Route::resource('employees', EmployeeController::class);
            Route::get('employees/statistics', [EmployeeStatisticsController::class, 'index'])->name('employees.statistics');
            Route::resource('job-positions', JobPositionController::class)->except(['show']);
            Route::post('employees/export', [ExportController::class, 'employees'])->name('employees.export');
            Route::post('employees/export-full', [ExportController::class, 'employeesFullExport'])->name('employees.export-full');
            Route::post('employees/import', [ExportController::class, 'importEmployees'])->name('employees.import');
            Route::post('employees/import-full', [ExportController::class, 'importEmployeesFull'])->name('employees.import-full');

            Route::resource('leave-requests', LeaveRequestController::class)->only(['index', 'create', 'store', 'destroy']);
            Route::prefix('leave-approvals')->name('leave-approvals.')->group(function () {
                Route::get('/', [LeaveApprovalController::class, 'index'])->name('index');
                Route::post('{leaveRequest}/approve', [LeaveApprovalController::class, 'approve'])->name('approve');
                Route::post('{leaveRequest}/reject', [LeaveApprovalController::class, 'reject'])->name('reject');
            });
            Route::resource('leave-policies', LeavePolicyController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::get('attendances', [HrAttendanceController::class, 'index'])->name('attendances.index');
            Route::post('attendances', [HrAttendanceController::class, 'store'])->name('attendances.store');
            Route::post('attendances/{employeeId}', [HrAttendanceController::class, 'update'])->name('attendances.update');
            Route::get('timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
            Route::get('timesheets/print', [TimesheetController::class, 'print'])->name('timesheets.print');
        });

        Route::prefix('tech')->name('tech.')->group(function () {
            Route::resource('issues', TechIssueController::class);
            Route::post('issues/{issue}/respond', [TechIssueController::class, 'respond'])->name('issues.respond');
            Route::resource('equipment', TechEquipmentController::class);
            Route::get('statistics', [TechStatisticsController::class, 'index'])->name('statistics');
            Route::get('emails', [TechController::class, 'emails'])->name('emails');
        });

        // Logistics
        Route::prefix('logistics')->name('logistics.')->group(function () {
            Route::get('statistics', [LogisticsStatisticsController::class, 'index'])->name('statistics');
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::resource('approval-rules', ApprovalRuleController::class)->except(['show']);
            Route::get('purchase-requests/help', [PurchaseRequestController::class, 'help'])->name('purchase-requests.help');
            Route::resource('purchase-requests', PurchaseRequestController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
            Route::get('purchase-requests/{purchaseRequest}/price', [PurchaseRequestController::class, 'priceForm'])->name('purchase-requests.price-form');
            Route::post('purchase-requests/{purchaseRequest}/price', [PurchaseRequestController::class, 'price'])->name('purchase-requests.price');
            Route::post('purchase-requests/{purchaseRequest}/manager-decide', [PurchaseRequestController::class, 'managerDecide'])->name('purchase-requests.manager-decide');
            Route::post('purchase-requests/{purchaseRequest}/pm2-decide', [PurchaseRequestController::class, 'pm2Decide'])->name('purchase-requests.pm2-decide');
            Route::post('purchase-requests/{purchaseRequest}/finance-decide', [PurchaseRequestController::class, 'financeDecide'])->name('purchase-requests.finance-decide');
            Route::post('purchase-requests/{purchaseRequest}/executive-decide', [PurchaseRequestController::class, 'executiveDecide'])->name('purchase-requests.executive-decide');
            Route::post('purchase-requests/{purchaseRequest}/refer', [PurchaseRequestController::class, 'refer'])->name('purchase-requests.refer');
            Route::post('purchase-requests/{purchaseRequest}/execute', [PurchaseRequestController::class, 'execute'])->name('purchase-requests.execute');
            Route::post('purchase-requests/{purchaseRequest}/approve', [PurchaseRequestApprovalController::class, 'approve'])->name('purchase-requests.approve');
            Route::post('purchase-requests/{purchaseRequest}/reject', [PurchaseRequestApprovalController::class, 'reject'])->name('purchase-requests.reject');
            Route::resource('warehouses', WarehouseController::class)->except(['show']);
            Route::get('warehouses/{warehouse}/items', [WarehouseItemController::class, 'index'])->name('warehouses.items.index');
            Route::get('warehouses/{warehouse}/items/create', [WarehouseItemController::class, 'create'])->name('warehouses.items.create');
            Route::post('warehouses/{warehouse}/items', [WarehouseItemController::class, 'store'])->name('warehouses.items.store');
            Route::get('warehouses/{warehouse}/items/{item}/edit', [WarehouseItemController::class, 'edit'])->name('warehouses.items.edit');
            Route::put('warehouses/{warehouse}/items/{item}', [WarehouseItemController::class, 'update'])->name('warehouses.items.update');
            Route::post('warehouses/{warehouse}/items/{item}/delete', [WarehouseItemController::class, 'destroy'])->name('warehouses.items.destroy');
            Route::get('deleted-items', [WarehouseItemController::class, 'deleted'])->name('warehouses.items.deleted');
            Route::resource('assets', AssetController::class);

            // Export/Import
            Route::get('export/purchase-requests', [LogisticsExportController::class, 'exportPurchaseRequests'])->name('export.purchase-requests');
            Route::post('import/purchase-requests', [LogisticsExportController::class, 'importPurchaseRequests'])->name('import.purchase-requests');
            Route::get('export/warehouses', [LogisticsExportController::class, 'exportWarehouses'])->name('export.warehouses');
            Route::post('import/warehouses', [LogisticsExportController::class, 'importWarehouses'])->name('import.warehouses');
            Route::get('export/assets', [LogisticsExportController::class, 'exportAssets'])->name('export.assets');
            Route::post('import/assets', [LogisticsExportController::class, 'importAssets'])->name('import.assets');
        });
    });

});

// Public certificate verification
Route::get('verify-certificate/{hash}', [CertificateController::class, 'verifyCertificate']);
Route::get('certificate/{hash}', [CertificateController::class, 'publicPreview'])->name('certificate.public-preview');

require __DIR__.'/auth.php';
