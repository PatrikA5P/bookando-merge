<?php

use App\Http\Controllers\AbsenceController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BundleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PartnerController;
use App\Http\Controllers\PricingRuleController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShiftController;
use App\Http\Controllers\TimeEntryController;
use App\Http\Controllers\VoucherController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // ── Public ────────────────────────────────────────────────────────
    Route::post('/auth/login', [AuthController::class, 'login']);

    // ── Protected ────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

        // Customers CRUD
        Route::apiResource('customers', CustomerController::class);

        // Employees CRUD
        Route::apiResource('employees', EmployeeController::class);
        Route::patch('/employees/{employee}/status', [EmployeeController::class, 'updateStatus']);

        // Services CRUD
        Route::apiResource('services', ServiceController::class);

        // Categories CRUD
        Route::apiResource('categories', CategoryController::class);

        // Appointments CRUD
        Route::apiResource('appointments', AppointmentController::class);
        Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus']);
        Route::get('/appointments/available-slots', [AppointmentController::class, 'availableSlots']);

        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send']);
        Route::post('/invoices/{invoice}/mark-paid', [InvoiceController::class, 'markPaid']);
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
        Route::post('/invoices/{invoice}/remind', [InvoiceController::class, 'sendReminder']);

        // Finance
        Route::get('/finance/journal-entries', [FinanceController::class, 'journalEntries']);
        Route::get('/finance/chart-accounts', [FinanceController::class, 'chartAccounts']);
        Route::get('/finance/salary-declarations', [FinanceController::class, 'salaryDeclarations']);
        Route::patch('/finance/salary-declarations/{id}/submit', [FinanceController::class, 'submitSalary']);

        // Workday
        Route::apiResource('time-entries', TimeEntryController::class);
        Route::apiResource('shifts', ShiftController::class);
        Route::apiResource('absences', AbsenceController::class);

        // Academy
        Route::apiResource('courses', CourseController::class);
        Route::apiResource('courses.lessons', LessonController::class)->shallow();

        // Resources
        Route::apiResource('locations', LocationController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('equipment', EquipmentController::class);

        // Partners
        Route::apiResource('partners', PartnerController::class);

        // Offers
        Route::apiResource('vouchers', VoucherController::class);
        Route::apiResource('bundles', BundleController::class);
        Route::apiResource('pricing-rules', PricingRuleController::class);

        // Settings
        Route::get('/settings', [SettingsController::class, 'index']);
        Route::put('/settings', [SettingsController::class, 'update']);

        // Audit
        Route::get('/audit-logs', [AuditLogController::class, 'index']);
    });
});
