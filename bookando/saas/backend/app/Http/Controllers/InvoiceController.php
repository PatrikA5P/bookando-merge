<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLineItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/invoices
     *
     * Paginated list with optional status filter and search by number/customer name.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = Invoice::where('invoices.tenant_id', $tenantId);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('invoices.status', $request->input('status'));
        }

        // Search by invoice number or customer name
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('invoices.number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->orderByDesc('invoices.created_at')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * POST /api/v1/invoices
     *
     * Create a new invoice with line items.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoices')->where('tenant_id', $tenantId),
            ],
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'status' => ['nullable', 'string', Rule::in(['DRAFT', 'SENT', 'PAID', 'OVERDUE', 'CANCELLED'])],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'total_minor' => ['required', 'integer', 'min:0'],
            'tax_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'dunning_level' => ['nullable', 'integer', 'min:0', 'max:3'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:500'],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0'],
            'line_items.*.unit_price_minor' => ['required_with:line_items', 'integer', 'min:0'],
            'line_items.*.total_minor' => ['required_with:line_items', 'integer', 'min:0'],
            'line_items.*.vat_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['status'] = $validated['status'] ?? 'DRAFT';
        $validated['currency'] = $validated['currency'] ?? 'CHF';
        $validated['tax_minor'] = $validated['tax_minor'] ?? 0;
        $validated['dunning_level'] = $validated['dunning_level'] ?? 0;

        // Auto-generate QR reference (26 random digits)
        $validated['qr_reference'] = str_pad((string) random_int(0, 10 ** 13 - 1), 13, '0', STR_PAD_LEFT)
            . str_pad((string) random_int(0, 10 ** 13 - 1), 13, '0', STR_PAD_LEFT);

        $lineItems = $validated['line_items'] ?? [];
        unset($validated['line_items']);

        $invoice = DB::transaction(function () use ($validated, $lineItems) {
            $invoice = Invoice::create($validated);

            foreach ($lineItems as $item) {
                $invoice->lineItems()->create([
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_minor' => $item['unit_price_minor'],
                    'total_minor' => $item['total_minor'],
                    'vat_rate_percent' => $item['vat_rate_percent'] ?? 8.1,
                ]);
            }

            return $invoice;
        });

        $invoice->load('lineItems');

        return response()->json([
            'data' => $this->transformModel($invoice),
        ], 201);
    }

    /**
     * GET /api/v1/invoices/{id}
     *
     * Single invoice with line items eager loaded.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)
            ->with('lineItems')
            ->findOrFail($id);

        return response()->json([
            'data' => $this->transformModel($invoice),
        ]);
    }

    /**
     * PUT /api/v1/invoices/{id}
     *
     * Update invoice and sync line items.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        $validated = $request->validate([
            'number' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('invoices')->where('tenant_id', $tenantId)->ignore($invoice->id),
            ],
            'customer_id' => ['sometimes', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'status' => ['sometimes', 'string', Rule::in(['DRAFT', 'SENT', 'PAID', 'OVERDUE', 'CANCELLED'])],
            'issue_date' => ['sometimes', 'date'],
            'due_date' => ['sometimes', 'date', 'after_or_equal:issue_date'],
            'total_minor' => ['sometimes', 'integer', 'min:0'],
            'tax_minor' => ['nullable', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:3'],
            'dunning_level' => ['nullable', 'integer', 'min:0', 'max:3'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string', 'max:500'],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0'],
            'line_items.*.unit_price_minor' => ['required_with:line_items', 'integer', 'min:0'],
            'line_items.*.total_minor' => ['required_with:line_items', 'integer', 'min:0'],
            'line_items.*.vat_rate_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $lineItems = $validated['line_items'] ?? null;
        unset($validated['line_items']);

        DB::transaction(function () use ($invoice, $validated, $lineItems) {
            $invoice->update($validated);

            // Sync line items if provided: delete existing, re-create
            if ($lineItems !== null) {
                $invoice->lineItems()->delete();

                foreach ($lineItems as $item) {
                    $invoice->lineItems()->create([
                        'description' => $item['description'],
                        'quantity' => $item['quantity'],
                        'unit_price_minor' => $item['unit_price_minor'],
                        'total_minor' => $item['total_minor'],
                        'vat_rate_percent' => $item['vat_rate_percent'] ?? 8.1,
                    ]);
                }
            }
        });

        $invoice->load('lineItems');

        return response()->json([
            'data' => $this->transformModel($invoice->fresh()->load('lineItems')),
        ]);
    }

    /**
     * DELETE /api/v1/invoices/{id}
     *
     * Only allowed if status is DRAFT.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        if ($invoice->status !== 'DRAFT') {
            return response()->json([
                'message' => 'Only DRAFT invoices can be deleted.',
            ], 422);
        }

        $invoice->delete();

        return response()->json(null, 204);
    }

    /**
     * POST /api/v1/invoices/{id}/send
     *
     * Set status to SENT.
     */
    public function send(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        $invoice->update(['status' => 'SENT']);

        return response()->json([
            'data' => $this->transformModel($invoice->fresh()),
        ]);
    }

    /**
     * POST /api/v1/invoices/{id}/mark-paid
     *
     * Set status to PAID and dunning_level to 0.
     */
    public function markPaid(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        $invoice->update([
            'status' => 'PAID',
            'dunning_level' => 0,
        ]);

        return response()->json([
            'data' => $this->transformModel($invoice->fresh()),
        ]);
    }

    /**
     * POST /api/v1/invoices/{id}/cancel
     *
     * Set status to CANCELLED.
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        $invoice->update(['status' => 'CANCELLED']);

        return response()->json([
            'data' => $this->transformModel($invoice->fresh()),
        ]);
    }

    /**
     * POST /api/v1/invoices/{id}/remind
     *
     * Increment dunning_level (max 3) and set status to OVERDUE.
     */
    public function sendReminder(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($id);

        $newLevel = min($invoice->dunning_level + 1, 3);

        $invoice->update([
            'status' => 'OVERDUE',
            'dunning_level' => $newLevel,
        ]);

        return response()->json([
            'data' => $this->transformModel($invoice->fresh()),
        ]);
    }
}
