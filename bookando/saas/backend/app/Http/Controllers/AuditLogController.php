<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TransformsResponse;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    use TransformsResponse;

    /**
     * GET /api/v1/audit-logs
     *
     * Paginated audit logs, filterable by entity_type and action.
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = auth()->user()->tenant_id;
        $perPage = min((int) $request->input('per_page', 25), 100);

        $query = AuditLog::where('tenant_id', $tenantId);

        if ($request->filled('entity_type')) {
            $query->where('entity_type', $request->input('entity_type'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($this->paginatedResponse($paginator));
    }
}
