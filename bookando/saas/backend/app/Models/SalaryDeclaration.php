<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_id',
        'year',
        'month',
        'gross_minor',
        'ahv_minor',
        'alv_minor',
        'bvg_minor',
        'nbu_minor',
        'tax_minor',
        'net_minor',
        'status',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'gross_minor' => 'integer',
        'ahv_minor' => 'integer',
        'alv_minor' => 'integer',
        'bvg_minor' => 'integer',
        'nbu_minor' => 'integer',
        'tax_minor' => 'integer',
        'net_minor' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
