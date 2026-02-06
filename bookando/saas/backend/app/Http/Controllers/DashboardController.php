<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/dashboard/stats
     *
     * Returns aggregated stats for the authenticated tenant.
     */
    public function stats(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $customerCount = Customer::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DELETED')
            ->count();

        $employeeCount = Employee::where('tenant_id', $tenantId)
            ->where('status', 'ACTIVE')
            ->count();

        $appointmentsTodayCount = Appointment::where('tenant_id', $tenantId)
            ->whereDate('date', $today)
            ->count();

        $revenueThisMonthMinor = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'PAID')
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->sum('total_minor');

        $upcomingAppointments = Appointment::where('tenant_id', $tenantId)
            ->where('date', '>=', $today)
            ->where('status', '!=', 'CANCELLED')
            ->with(['customer', 'employee', 'service'])
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(5)
            ->get()
            ->map(fn ($appt) => $this->transformModel($appt, [
                'customerName' => $appt->customer?->first_name . ' ' . $appt->customer?->last_name,
                'employeeName' => $appt->employee?->first_name . ' ' . $appt->employee?->last_name,
                'serviceName' => $appt->service?->title,
            ]));

        $recentActivity = AuditLog::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($entry) => $this->transformModel($entry));

        return response()->json([
            'data' => [
                'customerCount' => $customerCount,
                'employeeCount' => $employeeCount,
                'appointmentsTodayCount' => $appointmentsTodayCount,
                'revenueThisMonthMinor' => (int) $revenueThisMonthMinor,
                'upcomingAppointments' => $upcomingAppointments->values()->toArray(),
                'recentActivity' => $recentActivity->values()->toArray(),
            ],
        ]);
    }
}
