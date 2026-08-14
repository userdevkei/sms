<?php

use App\Models\Stream;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;

use App\Http\Controllers\{AdminPaymentController, VehicleController, UserController, TransportRouteController, TermSubjectResultController, TermResultCompletionController, SubjectTeacherAssignmentController, StudentController, StreamController, RouteAssignmentController, RoomReservationController, RoomController, RoomAllocationController, RoleController, ReportCardPdfController, ReportCardController, ProgressionExceptionController, ProgressionController, PermissionController, PathwayController, PathwayClassificationController, MarksEntryController, LearningAreaController, HostelController, GradingBandController, GradeLevelController, EnrollmentController, EducationLevelController, DriverController, DashboardController, AssessmentTypeController, AssessmentController, AcademicTermController,
    BankWebhookController,
    ChangePasswordController,
    EmailGatewayController,
    MyPaymentsController,
    MyProfileController,
    MyResultsController,
    MyStatementController,
    PaymentGatewayController,
    SmsGatewayController,
    StudentPaymentController,
    StudentRouteStopController,
    VoteheadController,
    FeeStructureController,
    OtherChargeTypeController,
    OtherChargeController,
    ExemptionController,
    InvoiceController,
    PaymentController,
    StudentStatementController,};

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.view'])->group(function () {
    Route::get('voteheads', [VoteheadController::class, 'index'])->name('voteheads.index');
    Route::get('fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
    Route::get('fee-structures/{fee_structure}', [FeeStructureController::class, 'show'])->name('fee-structures.show');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.manage'])->group(function () {
    Route::post('voteheads', [VoteheadController::class, 'store'])->name('voteheads.store');
    Route::put('voteheads/{votehead}', [VoteheadController::class, 'update'])->name('voteheads.update');
    Route::delete('voteheads/{votehead}', [VoteheadController::class, 'destroy'])->name('voteheads.destroy');

    Route::get('fee-structures-create', [FeeStructureController::class, 'create'])->name('fee-structures.create');
    Route::post('fee-structures', [FeeStructureController::class, 'store'])->name('fee-structures.store');
    Route::delete('fee-structures/{fee_structure}', [FeeStructureController::class, 'destroy'])->name('fee-structures.destroy');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.approve'])->group(function () {
    Route::post('fee-structures/{fee_structure}/publish', [FeeStructureController::class, 'publish'])->name('fee-structures.publish');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:other_charges.view'])->group(function () {
    Route::get('other-charge-types', [OtherChargeTypeController::class, 'index'])->name('other-charge-types.index');
    Route::get('other-charges', [OtherChargeController::class, 'index'])->name('other-charges.index');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:other_charges.manage'])->group(function () {
    Route::post('other-charge-types', [OtherChargeTypeController::class, 'store'])->name('other-charge-types.store');
    Route::put('other-charge-types/{other_charge_type}', [OtherChargeTypeController::class, 'update'])->name('other-charge-types.update');
    Route::delete('other-charge-types/{other_charge_type}', [OtherChargeTypeController::class, 'destroy'])->name('other-charge-types.destroy');

    Route::get('other-charges-create', [OtherChargeController::class, 'create'])->name('other-charges.create');
    Route::post('other-charges', [OtherChargeController::class, 'store'])->name('other-charges.store');
    Route::delete('other-charges/{other_charge}', [OtherChargeController::class, 'destroy'])->name('other-charges.destroy');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.view'])->group(function () {
    Route::get('exemptions', [ExemptionController::class, 'index'])->name('exemptions.index');
});
Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.apply'])->group(function () {
    Route::get('exemptions-create', [ExemptionController::class, 'create'])->name('exemptions.create');
    Route::post('exemptions', [ExemptionController::class, 'store'])->name('exemptions.store');
});
Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.approve'])->group(function () {
    Route::post('exemptions/{exemption}/approve', [ExemptionController::class, 'approve'])->name('exemptions.approve');
    Route::post('exemptions/{exemption}/reject', [ExemptionController::class, 'reject'])->name('exemptions.reject');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:invoices.view'])->group(function () {
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices-data', [InvoiceController::class, 'data'])->name('invoices.data');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('students/{student}/statement', [StudentStatementController::class, 'show'])->name('statements.show');
});
Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:invoices.create'])->group(function () {
    Route::get('invoices-generate', [InvoiceController::class, 'generateForm'])->name('invoices.generate-form');
    Route::post('invoices/generate-bulk', [InvoiceController::class, 'generateBulk'])->name('invoices.generate-bulk');
    Route::post('invoices/generate-single', [InvoiceController::class, 'generateSingle'])->name('invoices.generate-single');
});

Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:payments.view'])->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments-data', [PaymentController::class, 'data'])->name('payments.data');
});
Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:payments.record'])->group(function () {
    Route::get('payments-create', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
});

Route::get('/files/{path}', function ($path) {
    $fullPath = base_path($path);
    if (!file_exists($fullPath)) {abort(404);}
    $mimeType = mime_content_type($fullPath);
    return response()->file($fullPath, ['Content-Type' => $mimeType, 'Cache-Control' => 'public, max-age=31536000',]);
})->where('path', '.*')->name('file');

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');
});


Route::middleware('auth')->group(function () {
    Route::get('/locations-subcounties', function (Request $request) {
        $county = $request->query('county');
        $subcounties = collect(config('counties'))->get($county, []);

        return response()->json(array_keys($subcounties));
    })->name('locations.subcounties');

    Route::get('/locations-wards', function (Request $request) {
        $county = $request->query('county');
        $subcounty = $request->query('subcounty');
        $wards = collect(config('counties'))->get($county, [])[$subcounty] ?? [];

        return response()->json($wards);
    })->name('locations.wards');
});

Route::get('/grade-levels-streams', function (Request $request) {
    $gradeLevelId = $request->query('grade_level');

    $streams = Stream::where('grade_level_id', $gradeLevelId)
        ->where('status', 'active')
        ->orderBy('name')
        ->get(['id', 'name']);

    return response()->json($streams);
})->name('grade-levels.streams');


Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    Route::prefix('settings')->name('settings.')->group( function () {
        Route::get('/', [DashboardController::class, 'settings'])->name('index');
        Route::put('/', [DashboardController::class, 'updateSettings'])->name('update');


        Route::post('sms-gateways', [SmsGatewayController::class, 'store'])->name('sms-gateways.store');
        Route::patch('sms-gateways/{smsGateway}', [SmsGatewayController::class, 'update'])->name('sms-gateways.update');
        Route::post('sms-gateways/{smsGateway}/activate', [SmsGatewayController::class, 'activate'])->name('sms-gateways.activate');
        Route::delete('sms-gateways/{smsGateway}', [SmsGatewayController::class, 'destroy'])->name('sms-gateways.destroy');

        Route::post('payment-gateways', [PaymentGatewayController::class, 'store'])->name('payment-gateways.store');
        Route::patch('payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'update'])->name('payment-gateways.update');
        Route::post('payment-gateways/{paymentGateway}/activate', [PaymentGatewayController::class, 'activate'])->name('payment-gateways.activate');
        Route::delete('payment-gateways/{paymentGateway}', [PaymentGatewayController::class, 'destroy'])->name('payment-gateways.destroy');

        Route::post('email-gateways', [EmailGatewayController::class, 'store'])->name('email-gateways.store');
        Route::patch('email-gateways/{emailGateway}', [EmailGatewayController::class, 'update'])->name('email-gateways.update');
        Route::post('email-gateways/{emailGateway}/activate', [EmailGatewayController::class, 'activate'])->name('email-gateways.activate');
        Route::delete('email-gateways/{emailGateway}', [EmailGatewayController::class, 'destroy'])->name('email-gateways.destroy');
    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/users-data', [UserController::class, 'data'])->name('data')->middleware('can:users.view');
        Route::get('/users-create', [UserController::class, 'create'])->name('create')->middleware('can:users.manage');
        Route::post('/users', [UserController::class, 'store'])->name('store')->middleware('can:users.manage');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit')->middleware('can:users.manage');
        Route::get('/{user}/profile', [UserController::class, 'profile'])->name('profile')->middleware('can:users.manage');
        Route::patch('/{user}/status', [UserController::class, 'toggleStatus'])->name('toggleStatus')->middleware('can:users.manage');
        Route::put('/{user}', [UserController::class, 'update'])->name('update')->middleware('can:users.manage');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy')->middleware('can:users.manage');

        Route::get('export-pdf', [UserController::class, 'exportPdf'])->name('export.pdf')->middleware('can:users.view');
        Route::get('export-excel', [UserController::class, 'exportExcel'])->name('export.excel')->middleware('can:users.view');
    });

    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/', [StudentController::class, 'index'])->name('index')->middleware('can:students.view');
        Route::get('/students-data', [StudentController::class, 'data'])->name('data')->middleware('can:students.view');
        Route::get('/students-create', [StudentController::class, 'create'])->name('create')->middleware('can:students.manage');
        Route::post('/students', [StudentController::class, 'store'])->name('store')->middleware('can:students.manage');
        Route::get('/{student}/edit', [StudentController::class, 'edit'])->name('edit')->middleware('can:students.manage');
        Route::get('/{student}/profile', [StudentController::class, 'profile'])->name('profile')->middleware('can:students.view');
        Route::put('/{student}', [StudentController::class, 'update'])->name('update')->middleware('can:students.manage');
        Route::delete('/{student}', [StudentController::class, 'destroy'])->name('destroy')->middleware('can:students.manage');
        Route::patch('/{user}/status', [StudentController::class, 'toggleStatus'])->name('toggleStatus')->middleware('can:users.manage');

        Route::get('import', [StudentController::class, 'import'])->name('import.create')->middleware('can:students.manage');
        Route::post('import-preview', [StudentController::class, 'preview'])->name('import.preview')->middleware('can:students.manage');
        Route::post('import', [StudentController::class, 'importUsers'])->name('import.store')->middleware('can:students.manage');
        Route::get('import-template', [StudentController::class, 'template'])->name('import.template')->middleware('can:students.manage');

        Route::get('export-pdf', [StudentController::class, 'exportPdf'])->name('export.pdf')->middleware('can:users.view');
        Route::get('export-excel', [StudentController::class, 'exportExcel'])->name('export.excel')->middleware('can:users.view');

        Route::get('/students/{viewedUser}/statement/pdf', [MyStatementController::class, 'pdf'])
            ->name('statement.pdf')
            ->middleware('can:students.statements');
    });

    Route::prefix('roles')->name('roles.')->group( function () {
        Route::get('/', [RoleController::class, 'index'])->name('index')->middleware('can:roles.manage');
        Route::get('create', [RoleController::class, 'create'])->name('create')->middleware('can:roles.manage');
        Route::post('/roles', [RoleController::class, 'store'])->name('store')->middleware('can:roles.manage');
        Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit')->middleware('can:roles.manage');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update')->middleware('can:roles.manage');
        Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy')->middleware('can:roles.manage');

    });
    Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index')->middleware('can:roles.manage');


    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:curriculum.view'])->group(function () {
        Route::get('education-levels', [EducationLevelController::class, 'index'])->name('education-levels.index');
        Route::get('grade-levels', [GradeLevelController::class, 'index'])->name('grade-levels.index');
        Route::get('learning-areas', [LearningAreaController::class, 'index'])->name('learning-areas.index');
        Route::get('pathways', [PathwayController::class, 'index'])->name('pathways.index');
        Route::get('streams', [StreamController::class, 'index'])->name('streams.index');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:curriculum.manage'])->group(function () {
        Route::post('education-levels', [EducationLevelController::class, 'store'])->name('education-levels.store');
        Route::put('education-levels/{education_level}', [EducationLevelController::class, 'update'])->name('education-levels.update');
        Route::delete('education-levels/{education_level}', [EducationLevelController::class, 'destroy'])->name('education-levels.destroy');

        Route::get('grade-levels-create', [GradeLevelController::class, 'create'])->name('grade-levels.create');
        Route::post('grade-levels', [GradeLevelController::class, 'store'])->name('grade-levels.store');
        Route::get('grade-levels/{grade_level}/edit', [GradeLevelController::class, 'edit'])->name('grade-levels.edit');
        Route::put('grade-levels/{grade_level}', [GradeLevelController::class, 'update'])->name('grade-levels.update');
        Route::delete('grade-levels/{grade_level}', [GradeLevelController::class, 'destroy'])->name('grade-levels.destroy');

        Route::get('learning-areas-create', [LearningAreaController::class, 'create'])->name('learning-areas.create');
        Route::post('learning-areas', [LearningAreaController::class, 'store'])->name('learning-areas.store');
        Route::get('learning-areas/{learning_area}/edit', [LearningAreaController::class, 'edit'])->name('learning-areas.edit');
        Route::put('learning-areas/{learning_area}', [LearningAreaController::class, 'update'])->name('learning-areas.update');
        Route::delete('learning-areas/{learning_area}', [LearningAreaController::class, 'destroy'])->name('learning-areas.destroy');

        Route::get('pathways-create', [PathwayController::class, 'create'])->name('pathways.create');
        Route::post('pathways', [PathwayController::class, 'store'])->name('pathways.store');
        Route::get('pathways/{pathway}/edit', [PathwayController::class, 'edit'])->name('pathways.edit');
        Route::put('pathways/{pathway}', [PathwayController::class, 'update'])->name('pathways.update');
        Route::delete('pathways/{pathway}', [PathwayController::class, 'destroy'])->name('pathways.destroy');

        Route::get('streams-create', [StreamController::class, 'create'])->name('streams.create');
        Route::post('streams', [StreamController::class, 'store'])->name('streams.store');
        Route::get('streams/{stream}/edit', [StreamController::class, 'edit'])->name('streams.edit');
        Route::put('streams/{stream}', [StreamController::class, 'update'])->name('streams.update');
        Route::delete('streams/{stream}', [StreamController::class, 'destroy'])->name('streams.destroy');
    });

    Route::prefix('transport')->name('transport.')->middleware(['auth', 'can:transport.view'])->group(function () {
        Route::get('vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::get('vehicles-data', [VehicleController::class, 'data'])->name('vehicles.data');
        Route::get('vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');

        Route::get('drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::get('drivers-data', [DriverController::class, 'data'])->name('drivers.data');

        Route::get('routes', [TransportRouteController::class, 'index'])->name('transport-routes.index');
        Route::get('routes-data', [TransportRouteController::class, 'data'])->name('transport-routes.data');
        Route::get('routes/{transportRoute}', [TransportRouteController::class, 'show'])->name('transport-routes.show');

        Route::get('assignments', [RouteAssignmentController::class, 'index'])->name('route-assignments.index');
        Route::get('assignments-data', [RouteAssignmentController::class, 'data'])->name('route-assignments.data');
    });

    Route::prefix('transport')->name('transport.')->middleware(['auth', 'can:transport.manage'])->group(function () {
        Route::get('vehicles-create', [VehicleController::class, 'create'])->name('vehicles.create');
        Route::post('vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::get('vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
        Route::put('vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
        Route::post('vehicles/{vehicle}/maintenance', [VehicleController::class, 'storeMaintenanceLog'])->name('vehicles.maintenance.store');

        Route::get('drivers-create', [DriverController::class, 'create'])->name('drivers.create');
        Route::post('drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::get('drivers/{driver}/edit', [DriverController::class, 'edit'])->name('drivers.edit');
        Route::post('drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        Route::get('routes-create', [TransportRouteController::class, 'create'])->name('transport-routes.create');
        Route::post('routes', [TransportRouteController::class, 'store'])->name('transport-routes.store');
        Route::get('routes/{transportRoute}/edit', [TransportRouteController::class, 'edit'])->name('transport-routes.edit');
        Route::put('routes/{transportRoute}/update', [TransportRouteController::class, 'update'])->name('transport-routes.update');
        Route::delete('routes/{transportRoute}', [TransportRouteController::class, 'destroy'])->name('transport-routes.destroy');

        // FIXED: was 'transport/assignments-create', which doubled the group's
        // own 'transport' prefix into /transport/transport/assignments-create —
        // that URL matched nothing, so the request never reached this controller.
        Route::post('assignments-create', [RouteAssignmentController::class, 'store'])->name('route-assignments.store');
        Route::post('assignments/{routeAssignment}/end', [RouteAssignmentController::class, 'end'])->name('route-assignments.end');
        Route::delete('assignments/{routeAssignment}', [RouteAssignmentController::class, 'destroy'])->name('route-assignments.destroy');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:curriculum.view'])->group(function () {
        Route::get('academic-terms', [AcademicTermController::class, 'index'])->name('academic-terms.index');
        Route::get('grade-levels/{grade_level}/streams', [EnrollmentController::class, 'streamsForGrade'])->name('enrollments.streams-for-grade');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:curriculum.manage'])->group(function () {
        Route::get('students/{student}/enroll', [EnrollmentController::class, 'create'])->name('enrollments.create');
        Route::post('students/{student}/enroll', [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::get('progression/{grade_level}/exceptions-create', [ProgressionExceptionController::class, 'create'])->name('progression.exceptions.create');
        Route::post('progression/{grade_level}/exceptions', [ProgressionExceptionController::class, 'store'])->name('progression.exceptions.store');
        Route::post('academic-terms', [AcademicTermController::class, 'store'])->name('academic-terms.store');
        Route::delete('academic-terms/{academic_term}', [AcademicTermController::class, 'destroy'])->name('academic-terms.destroy');
        Route::put('academic-terms/{academicTerm}', [AcademicTermController::class, 'update'])->name('academic-terms.update');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:progression.view'])->group(function () {
        Route::get('progression', [ProgressionController::class, 'index'])->name('progression.index');
        Route::get('progression/{grade_level}', [ProgressionController::class, 'show'])->name('progression.show');
        Route::get('progression-exceptions', [ProgressionExceptionController::class, 'index'])->name('progression.exceptions.index');
        Route::get('term-results/{grade_level}', [TermResultCompletionController::class, 'edit'])->name('term-results.edit');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:progression.initiate'])->group(function () {
        Route::post('progression/{grade_level}/promote-all', [ProgressionController::class, 'promoteAll'])->name('progression.promote-all');
        Route::get('progression/{grade_level}/classify-pathways', [PathwayClassificationController::class, 'create'])->name('progression.classify-pathways.create');
        Route::post('progression/{grade_level}/classify-pathways', [PathwayClassificationController::class, 'store'])->name('progression.classify-pathways.store');
        Route::put('term-results/{grade_level}', [TermResultCompletionController::class, 'update'])->name('term-results.update');
    });

    Route::prefix('curriculum')->name('curriculum.')->middleware(['auth', 'can:progression.approve'])->group(function () {
        Route::post('progression-exceptions/{exception}/approve', [ProgressionExceptionController::class, 'approve'])->name('progression.exceptions.approve');
        Route::post('progression-exceptions/{exception}/reject', [ProgressionExceptionController::class, 'reject'])->name('progression.exceptions.reject');
    });

    Route::prefix('results')->name('results.')->middleware(['auth', 'can:results.view'])->group(function () {
        Route::get('assessment-types', [AssessmentTypeController::class, 'index'])->name('assessment-types.index');
        Route::get('assignments', [SubjectTeacherAssignmentController::class, 'index'])->name('assignments.index');
//        Route::get('assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('assessments', [AssessmentController::class, 'index'])->name('assessments.index');
        Route::get('assessments/rounds/{academicTerm}/{name}', [AssessmentController::class, 'roundShow'])->name('assessments.round');
        Route::get('assessments/rounds/{academicTerm}/{name}/data', [AssessmentController::class, 'roundData'])->name('assessments.round-data');
        Route::get('assessments-data', [AssessmentController::class, 'data'])->name('assessments.data');
        Route::get('assessments/{assessment}/marks-entry', [MarksEntryController::class, 'edit'])->name('assessments.marks-entry');
        Route::get('term-subject/{stream}/{learning_area}/{academic_term}', [TermSubjectResultController::class, 'preview'])->name('term-subject.preview');

        Route::get('report-cards', [ReportCardController::class, 'index'])->name('report-cards.index');
        Route::get('report-cards/assessment-rounds', [ReportCardController::class, 'assessmentRounds'])->name('report-cards.assessment-rounds');

// Term flow
        Route::get('report-cards/{stream}/{academic_term}/review', [ReportCardController::class, 'review'])->name('report-cards.review');
        Route::get('report-cards/{term_overall_result}/pdf', [ReportCardPdfController::class, 'show'])->name('report-cards.pdf');
        Route::get('report-cards/{stream}/{academic_term}/pdf-bulk', [ReportCardPdfController::class, 'showBulk'])->name('report-cards.pdf-bulk');

// Single-round (exam) flow
        Route::get('report-cards/assessment/{stream}/{academicTerm}/{name}', [ReportCardController::class, 'assessmentReview'])->name('report-cards.assessment-review');
        Route::get('report-cards/assessment/{stream}/{academicTerm}/{name}/pdf/{studentEnrollment}', [ReportCardPdfController::class, 'showAssessment'])->name('report-cards.assessment-pdf');
        Route::get('report-cards/assessment/{stream}/{academicTerm}/{name}/pdf-bulk', [ReportCardPdfController::class, 'showAssessmentBulk'])->name('report-cards.assessment-pdf-bulk');

// Year flow
        Route::get('report-cards/year/{stream}/{academicYear}', [ReportCardController::class, 'yearReview'])->name('report-cards.year-review');
        Route::get('report-cards/year/{stream}/{academicYear}/pdf/{studentEnrollment}', [ReportCardPdfController::class, 'showYear'])->name('report-cards.year-pdf');
        Route::get('report-cards/year/{stream}/{academicYear}/pdf-bulk', [ReportCardPdfController::class, 'showYearBulk'])->name('report-cards.year-pdf-bulk');

// Writes - keep in whichever permission-gated group they already live in
        Route::post('report-cards/compile', [ReportCardController::class, 'compile'])->name('report-cards.compile');
        Route::post('report-cards/publish', [ReportCardController::class, 'publish'])->name('report-cards.publish');
    });

    Route::prefix('results')->name('results.')->middleware(['auth', 'can:curriculum.manage'])->group(function () {
        Route::post('assessment-types', [AssessmentTypeController::class, 'store'])->name('assessment-types.store');
        Route::put('assessment-types/{assessment_type}', [AssessmentTypeController::class, 'update'])->name('assessment-types.update');
        Route::delete('assessment-types/{assessment_type}', [AssessmentTypeController::class, 'destroy'])->name('assessment-types.destroy');

        Route::get('assignments-create', [SubjectTeacherAssignmentController::class, 'create'])->name('assignments.create');
        Route::post('assignments', [SubjectTeacherAssignmentController::class, 'store'])->name('assignments.store');
        Route::delete('assignments/{assignment}', [SubjectTeacherAssignmentController::class, 'destroy'])->name('assignments.destroy');

    });

    Route::middleware(['auth', 'can:results.enter_marks'])->group(function () {
        Route::get('results/assessments-create', [AssessmentController::class, 'create'])->name('results.assessments.create');
        Route::post('results/assessments/store', [AssessmentController::class, 'store'])->name('results.assessments.store');
        Route::post('results/assessments/{assessment}/open', [AssessmentController::class, 'open'])->name('results.assessments.open');
        Route::post('results/assessments/{assessment}/lock', [AssessmentController::class, 'lock'])->name('results.assessments.lock');
        Route::delete('results/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('results.assessments.destroy');
        Route::put('results/assessments/{assessment}/marks', [MarksEntryController::class, 'update'])->name('results.marks-entry.update');
        Route::post('results/term-subject/{stream}/{learning_area}/{academic_term}/finalize', [TermSubjectResultController::class, 'finalize'])->name('results.term-subject.finalize');
    });

    Route::middleware(['auth', 'can:results.approve'])->group(function () {
        Route::post('results/report-cards/compile', [ReportCardController::class, 'compile'])->name('results.report-cards.compile');
    });

    Route::middleware(['auth', 'can:results.publish'])->group(function () {
        Route::post('results/report-cards/publish', [ReportCardController::class, 'publish'])->name('results.report-cards.publish');
    });

    Route::prefix('accommodation')->name('accommodation.')->middleware(['auth', 'can:accommodation.view'])->group(function () {
        Route::get('hostels', [HostelController::class, 'index'])->name('hostels.index');
        Route::get('hostels/{hostel}', [HostelController::class, 'show'])->name('hostels.show');

        Route::get('reservations', [RoomReservationController::class, 'index'])->name('reservations.index');
        Route::get('hostels/{hostel}/rooms-with-space', [RoomReservationController::class, 'roomsForHostel'])->name('reservations.rooms-for-hostel');

        Route::get('allocations', [RoomAllocationController::class, 'index'])->name('allocations.index');
    });

    Route::prefix('accommodation')->name('accommodation.')->middleware(['auth', 'can:accommodation.manage'])->group(function () {
        Route::get('hostels-create', [HostelController::class, 'create'])->name('hostels.create');
        Route::post('hostels', [HostelController::class, 'store'])->name('hostels.store');
        Route::get('hostels/{hostel}/edit', [HostelController::class, 'edit'])->name('hostels.edit');
        Route::put('hostels/{hostel}', [HostelController::class, 'update'])->name('hostels.update');
        Route::delete('hostels/{hostel}', [HostelController::class, 'destroy'])->name('hostels.destroy');

        Route::post('hostels/{hostel}/rooms', [RoomController::class, 'store'])->name('rooms.store');
        Route::put('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        Route::get('reservations-create', [RoomReservationController::class, 'create'])->name('reservations.create');
        Route::post('reservations', [RoomReservationController::class, 'store'])->name('reservations.store');
        Route::post('reservations/{reservation}/approve', [RoomReservationController::class, 'approve'])->name('reservations.approve');
        Route::post('reservations/{reservation}/reject', [RoomReservationController::class, 'reject'])->name('reservations.reject');
        Route::delete('reservations/{reservation}', [RoomReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::get('allocations-create', [RoomAllocationController::class, 'create'])->name('allocations.create');
        Route::post('allocations', [RoomAllocationController::class, 'store'])->name('allocations.store');
        Route::post('allocations/{allocation}/vacate', [RoomAllocationController::class, 'vacate'])->name('allocations.vacate');
        Route::delete('allocations/{allocation}', [RoomAllocationController::class, 'destroy'])->name('allocations.destroy');
    });

    Route::prefix('results')->name('results.')->middleware(['auth', 'can:curriculum.manage'])->group(function () {
        Route::get('grading-bands', [GradingBandController::class, 'index'])->name('grading-bands.index');
        Route::post('grading-bands', [GradingBandController::class, 'store'])->name('grading-bands.store');
        Route::put('grading-bands/{gradingBand}', [GradingBandController::class, 'update'])->name('grading-bands.update');
        Route::delete('grading-bands/{gradingBand}', [GradingBandController::class, 'destroy'])->name('grading-bands.destroy');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.view'])->group(function () {
        Route::get('voteheads', [VoteheadController::class, 'index'])->name('voteheads.index');
        Route::get('fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
        Route::get('fee-structures/{fee_structure}', [FeeStructureController::class, 'show'])->name('fee-structures.show');

        // Student-facing
        Route::get('statement', [StudentStatementController::class, 'show'])->name('finance.statement.show');
        Route::get('statement/pdf', [StudentStatementController::class, 'pdf'])->name('finance.statement.pdf');
        Route::post('statement/pay', [StudentPaymentController::class, 'pay'])->name('finance.statement.pay');

// Admin
        Route::get('payments', [AdminPaymentController::class, 'index'])->name('finance.payments.index');

    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.manage'])->group(function () {
        Route::post('voteheads', [VoteheadController::class, 'store'])->name('voteheads.store');
        Route::put('voteheads/{votehead}', [VoteheadController::class, 'update'])->name('voteheads.update');
        Route::delete('voteheads/{votehead}', [VoteheadController::class, 'destroy'])->name('voteheads.destroy');

        Route::get('fee-structures-create', [FeeStructureController::class, 'create'])->name('fee-structures.create');
        Route::post('fee-structures', [FeeStructureController::class, 'store'])->name('fee-structures.store');
        Route::delete('fee-structures/{fee_structure}', [FeeStructureController::class, 'destroy'])->name('fee-structures.destroy');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:fee_structures.approve'])->group(function () {
        Route::post('fee-structures/{fee_structure}/publish', [FeeStructureController::class, 'publish'])->name('fee-structures.publish');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:other_charges.view'])->group(function () {
        Route::get('other-charge-types', [OtherChargeTypeController::class, 'index'])->name('other-charge-types.index');
        Route::get('other-charges', [OtherChargeController::class, 'index'])->name('other-charges.index');
        Route::get('student-route-stops', [StudentRouteStopController::class, 'index'])->name('transport.student-route-stops.index');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:other_charges.manage'])->group(function () {
        Route::post('other-charge-types', [OtherChargeTypeController::class, 'store'])->name('other-charge-types.store');
        Route::patch('other-charge-types/{other_charge_type}', [OtherChargeTypeController::class, 'update'])->name('other-charge-types.update');
        Route::delete('other-charge-types/{other_charge_type}', [OtherChargeTypeController::class, 'destroy'])->name('other-charge-types.destroy');

        Route::get('other-charges-create', [OtherChargeController::class, 'create'])->name('other-charges.create');
        Route::post('other-charges', [OtherChargeController::class, 'store'])->name('other-charges.store');
        Route::delete('other-charges/{other_charge}', [OtherChargeController::class, 'destroy'])->name('other-charges.destroy');

        Route::get('student-route-stops/create', [StudentRouteStopController::class, 'create'])->name('transport.student-route-stops.create');
        Route::post('student-route-stops', [StudentRouteStopController::class, 'store'])->name('transport.student-route-stops.store');
        Route::delete('student-route-stops/{studentRouteStop}', [StudentRouteStopController::class, 'destroy'])->name('transport.student-route-stops.destroy');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.view'])->group(function () {
        Route::get('exemptions', [ExemptionController::class, 'index'])->name('exemptions.index');
    });
    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.apply'])->group(function () {
        Route::get('exemptions-create', [ExemptionController::class, 'create'])->name('exemptions.create');
        Route::post('exemptions', [ExemptionController::class, 'store'])->name('exemptions.store');
    });
    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:exemptions.approve'])->group(function () {
        Route::post('exemptions/{exemption}/approve', [ExemptionController::class, 'approve'])->name('exemptions.approve');
        Route::post('exemptions/{exemption}/reject', [ExemptionController::class, 'reject'])->name('exemptions.reject');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:invoices.view'])->group(function () {
        Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('invoices-data', [InvoiceController::class, 'data'])->name('invoices.data');
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('students/{student}/statement', [StudentStatementController::class, 'show'])->name('statements.show');
    });
    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:invoices.create'])->group(function () {
        Route::post('invoices/preview', [InvoiceController::class, 'preview'])->name('invoices.preview');
        Route::post('invoices/store-confirmed', [InvoiceController::class, 'storeConfirmed'])->name('invoices.store-confirmed');
        Route::get('invoices-generate', [InvoiceController::class, 'generateForm'])->name('invoices.generate-form');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:payments.view'])->group(function () {
        Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments-data', [PaymentController::class, 'data'])->name('payments.data');
        Route::get('payments-failed-data', [PaymentController::class, 'failedData'])->name('payments.failed-data');
        Route::get('payments/{transaction}/validate', [PaymentController::class, 'validateTransaction'])->name('payments.validate');
        Route::get('payments-export', [PaymentController::class, 'exportSuccessful'])->name('payments.export');
        Route::get('payments-failed-export', [PaymentController::class, 'exportFailed'])->name('payments.failed-export');
    });

    Route::prefix('finance')->name('finance.')->middleware(['auth', 'can:payments.record'])->group(function () {
        Route::get('payments-create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [PaymentController::class, 'store'])->name('payments.store');
        Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])->name('payments.destroy');
    });

    Route::prefix('results')->name('results.')->middleware(['auth', 'can:my-results.view'])->group(function () {
        Route::get('my-results', [MyResultsController::class, 'index'])->name('my-results.index');
        Route::get('my-report-cards/{term_overall_result}/pdf', [ReportCardPdfController::class, 'show'])->name('my-report-cards.pdf');
        Route::get('my-report-cards/year/{stream}/{academicYear}/pdf/{studentEnrollment}', [ReportCardPdfController::class, 'showYear'])->name('my-report-cards.year-pdf');
        Route::get('report-cards/assessment/{stream}/{academicTerm}/{name}/pdf/{studentEnrollment}', [ReportCardPdfController::class, 'showAssessment'])->name('my-report-cards.assessment-pdf');
    });

    Route::get('my-profile', [MyProfileController::class, 'show'])->name('profile.show');
    Route::get('change-password', [ChangePasswordController::class, 'show'])->name('password.change.show');
    Route::put('change-password', [ChangePasswordController::class, 'update'])->name('password.change.update');

    Route::get('my-profile', [MyProfileController::class, 'show'])->name('profile.show');
    Route::get('my-profile/edit', [MyProfileController::class, 'edit'])->name('profile.edit');
    Route::put('my-profile', [MyProfileController::class, 'update'])->name('profile.update');

    Route::get('my-statement', [MyStatementController::class, 'index'])->name('finance.my-statement');
    Route::get('my-statement/pdf', [MyStatementController::class, 'pdf'])->name('finance.my-statement.pdf');

    Route::get('my-payments', [MyPaymentsController::class, 'index'])->name('finance.my-payments');
    Route::post('my-payments/initiate', [MyPaymentsController::class, 'initiate'])->name('finance.my-payments.initiate');
    Route::post('my-payments/retry/{transaction}', [MyPaymentsController::class, 'retry'])->name('finance.my-payments.retry');
    Route::get('my-payments/status/{transaction}', [MyPaymentsController::class, 'status'])->name('finance.my-payments.status');

    // routes/web.php or api.php
});

Route::post('/mpesa/callback', [MyPaymentsController::class, 'handle'])->name('mpesa.callback')->withoutMiddleware(['auth', 'verified']); // adjust to whatever middleware wraps your web routes

// routes/web.php or a dedicated routes/webhooks.php
Route::prefix('webhooks/banks')->group(function () {
    Route::post('equity', [BankWebhookController::class, 'equity'])->withoutMiddleware(['auth', 'verified'])->name('webhooks.banks.equity');
    Route::post('coop', [BankWebhookController::class, 'coop'])->withoutMiddleware(['auth', 'verified'])->name('webhooks.banks.coop');
    Route::post('kcb', [BankWebhookController::class, 'kcb'])->withoutMiddleware(['auth', 'verified'])->name('webhooks.banks.kcb');
});

Route::post('settings/payment-gateways/kcb/fetch-ipn-signature', [BankWebhookController::class, 'fetch'])
    ->name('settings.payment-gateways.kcb.fetch-ipn-signature');
