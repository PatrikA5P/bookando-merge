<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'description',
        'discount_percent',
        'discount_minor',
        'usage_count',
        'max_uses',
        'expires_at',
        'active',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'discount_minor' => 'integer',
        'usage_count' => 'integer',
        'max_uses' => 'integer',
        'expires_at' => 'date',
        'active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
