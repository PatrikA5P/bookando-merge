<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AppointmentController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/appointments
     *
     * Paginated list with date range, employee, customer, status filters.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Appointment::where('tenant_id', $tenantId)
            ->with(['customer', 'employee', 'service']);

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->input('date_to'));
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by customer name, employee name, or notes
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('employee', function ($eq) use ($search) {
                        $eq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->orderBy('date')->orderBy('start_time')->paginate($perPage);

        // Transform with relation names
        $data = collect($paginator->items())->map(function ($appt) {
            return $this->transformModel($appt, [
                'customerName' => $appt->customer
                    ? trim($appt->customer->first_name . ' ' . $appt->customer->last_name)
                    : null,
                'employeeName' => $appt->employee
                    ? trim($appt->employee->first_name . ' ' . $appt->employee->last_name)
                    : null,
                'serviceName' => $appt->service?->title,
            ]);
        })->values()->toArray();

        return response()->json([
            'data' => $data,
            'meta' => [
                'page' => $paginator->currentPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'totalPages' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v1/appointments
     *
     * Create a new appointment.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(['SCHEDULED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = $validated['status'] ?? 'SCHEDULED';

        $appointment = Appointment::create($validated);
        $appointment->loadMissing(['customer', 'employee', 'service']);

        return response()->json([
            'data' => $this->transformModel($appointment, [
                'customerName' => $appointment->customer
                    ? trim($appointment->customer->first_name . ' ' . $appointment->customer->last_name)
                    : null,
                'employeeName' => $appointment->employee
                    ? trim($appointment->employee->first_name . ' ' . $appointment->employee->last_name)
                    : null,
                'serviceName' => $appointment->service?->title,
            ]),
        ], 201);
    }

    /**
     * GET /api/v1/appointments/{id}
     *
     * Single appointment with eager loaded relations.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $appointment = Appointment::where('tenant_id', $tenantId)
            ->with(['customer', 'employee', 'service'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($appointment, [
                'customerName' => $appointment->customer
                    ? trim($appointment->customer->first_name . ' ' . $appointment->customer->last_name)
                    : null,
                'employeeName' => $appointment->employee
                    ? trim($appointment->employee->first_name . ' ' . $appointment->employee->last_name)
                    : null,
                'serviceName' => $appointment->service?->title,
            ]),
        ]);
    }

    /**
     * PUT /api/v1/appointments/{id}
     *
     * Partial update.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'integer', 'exists:customers,id'],
            'employee_id' => ['sometimes', 'integer', 'exists:employees,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'date' => ['sometimes', 'date'],
            'start_time' => ['sometimes', 'date_format:H:i'],
            'end_time' => ['sometimes', 'date_format:H:i'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['SCHEDULED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])],
            'price_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $appointment->update($validated);
        $appointment = $appointment->fresh(['customer', 'employee', 'service']);

        return response()->json([
            'data' => $this->transformModel($appointment, [
                'customerName' => $appointment->customer
                    ? trim($appointment->customer->first_name . ' ' . $appointment->customer->last_name)
                    : null,
                'employeeName' => $appointment->employee
                    ? trim($appointment->employee->first_name . ' ' . $appointment->employee->last_name)
                    : null,
                'serviceName' => $appointment->service?->title,
            ]),
        ]);
    }

    /**
     * DELETE /api/v1/appointments/{id}
     *
     * Hard delete.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($id);
        $appointment->delete();

        return response()->json(null, 204);
    }

    /**
     * PATCH /api/v1/appointments/{id}/status
     *
     * Update appointment status.
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $appointment = Appointment::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['SCHEDULED', 'CONFIRMED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED', 'NO_SHOW'])],
        ]);

        $appointment->update(['status' => $validated['status']]);
        $appointment = $appointment->fresh(['customer', 'employee', 'service']);

        return response()->json([
            'data' => $this->transformModel($appointment, [
                'customerName' => $appointment->customer
                    ? trim($appointment->customer->first_name . ' ' . $appointment->customer->last_name)
                    : null,
                'employeeName' => $appointment->employee
                    ? trim($appointment->employee->first_name . ' ' . $appointment->employee->last_name)
                    : null,
                'serviceName' => $appointment->service?->title,
            ]),
        ]);
    }

    /**
     * GET /api/v1/appointments/available-slots
     *
     * Returns available time slots for a given date, employee, and duration.
     * Checks for conflicts against existing appointments.
     * Slots are generated in 30-minute intervals from 07:00 to 20:00.
     */
    public function availableSlots(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $request->validate([
            'date' => ['required', 'date'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'duration' => ['required', 'integer', 'min:1'],
        ]);

        $date = $request->input('date');
        $employeeId = (int) $request->input('employee_id');
        $duration = (int) $request->input('duration');

        // Fetch existing appointments for this employee on this date (excluding cancelled)
        $existingAppointments = Appointment::where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'CANCELLED')
            ->select(['start_time', 'end_time'])
            ->get();

        $slots = [];
        $startOfDay = Carbon::parse('07:00');
        $endOfDay = Carbon::parse('20:00');
        $intervalMinutes = 30;

        $current = $startOfDay->copy();

        while ($current->copy()->addMinutes($duration)->lte($endOfDay)) {
            $slotStart = $current->format('H:i');
            $slotEnd = $current->copy()->addMinutes($duration)->format('H:i');

            $hasConflict = false;

            foreach ($existingAppointments as $existing) {
                $existingStart = $existing->start_time;
                $existingEnd = $existing->end_time;

                // Normalize to H:i format for comparison
                if (strlen($existingStart) > 5) {
                    $existingStart = Carbon::parse($existingStart)->format('H:i');
                }
                if (strlen($existingEnd) > 5) {
                    $existingEnd = Carbon::parse($existingEnd)->format('H:i');
                }

                // Check overlap: slotStart < existingEnd AND slotEnd > existingStart
                if ($slotStart < $existingEnd && $slotEnd > $existingStart) {
                    $hasConflict = true;
                    break;
                }
            }

            if (!$hasConflict) {
                $slots[] = $slotStart;
            }

            $current->addMinutes($intervalMinutes);
        }

        return response()->json([
            'data' => $slots,
        ]);
    }
}
