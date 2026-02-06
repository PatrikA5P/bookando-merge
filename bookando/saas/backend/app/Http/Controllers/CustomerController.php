<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/customers
     *
     * Paginated list with optional search and status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Customer::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DELETED');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Search by name, email, or phone
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $paginator = $query->orderBy('last_name')->orderBy('first_name')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/customers
     *
     * Create a new customer.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers')->where('tenant_id', $tenantId),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'VIP', 'BLOCKED'])],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = $validated['status'] ?? 'ACTIVE';

        $customer = Customer::create($validated);

        return response()->json([
            'data' => $this->transformModel($customer),
        ], 201);
    }

    /**
     * GET /api/v1/customers/{id}
     *
     * Single customer with aggregated stats.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $customer = Customer::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DELETED')
            ->findOrFail($id);

        $totalBookings = Appointment::where('tenant_id', $tenantId)
            ->where('customer_id', $id)
            ->count();

        $totalRevenueMinor = Invoice::where('tenant_id', $tenantId)
            ->where('customer_id', $id)
            ->where('status', 'PAID')
            ->sum('total_minor');

        $openInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('customer_id', $id)
            ->whereIn('status', ['OPEN', 'OVERDUE', 'SENT'])
            ->count();

        return response()->json([
            'data' => $this->transformModel($customer, [
                'totalBookings' => $totalBookings,
                'totalRevenueMinor' => (int) $totalRevenueMinor,
                'openInvoices' => $openInvoices,
            ]),
        ]);
    }

    /**
     * PUT /api/v1/customers/{id}
     *
     * Partial update of a customer.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $customer = Customer::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DELETED')
            ->findOrFail($id);

        $validated = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('customers')->where('tenant_id', $tenantId)->ignore($customer->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'VIP', 'BLOCKED'])],
            'street' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:2'],
            'birthday' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $customer->update($validated);

        return response()->json([
            'data' => $this->transformModel($customer->fresh()),
        ]);
    }

    /**
     * DELETE /api/v1/customers/{id}
     *
     * Soft delete by setting status to DELETED.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $customer = Customer::where('tenant_id', $tenantId)
            ->where('status', '!=', 'DELETED')
            ->findOrFail($id);

        $customer->update(['status' => 'DELETED']);

        return response()->json(null, 204);
    }
}
