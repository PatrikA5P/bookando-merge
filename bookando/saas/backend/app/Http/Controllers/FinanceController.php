<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\ChartAccount;
use App\Models\JournalEntry;
use App\Models\SalaryDeclaration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinanceController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/finance/journal-entries
     *
     * Paginated journal entries for the tenant.
     */
    public function journalEntries(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = JournalEntry::where('tenant_id', $tenantId);

        $paginator = $query->orderByDesc('date')->orderByDesc('id')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * GET /api/v1/finance/chart-accounts
     *
     * All chart accounts grouped by type.
     */
    public function chartAccounts(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $accounts = ChartAccount::where('tenant_id', $tenantId)
            ->orderBy('number')
            ->get();

        $grouped = $accounts->groupBy('type')->map(function ($group) {
            return $this->transformCollection($group);
        })->toArray();

        return response()->json([
            'data' => $grouped,
        ]);
    }

    /**
     * GET /api/v1/finance/salary-declarations
     *
     * Paginated salary declarations, filterable by year/month.
     */
    public function salaryDeclarations(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = SalaryDeclaration::where('tenant_id', $tenantId);

        if ($request->filled('year')) {
            $query->where('year', (int) $request->input('year'));
        }

        if ($request->filled('month')) {
            $query->where('month', (int) $request->input('month'));
        }

        $paginator = $query->orderByDesc('year')
            ->orderByDesc('month')
            ->orderBy('employee_id')
            ->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }

    /**
     * PATCH /api/v1/finance/salary-declarations/{id}/submit
     *
     * Change status to SUBMITTED.
     */
    public function submitSalary(Request $request, int $id): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;

        $declaration = SalaryDeclaration::where('tenant_id', $tenantId)->findOrFail($id);

        $declaration->update(['status' => 'SUBMITTED']);

        return response()->json([
            'data' => $this->transformModel($declaration->fresh()),
        ]);
    }
}
