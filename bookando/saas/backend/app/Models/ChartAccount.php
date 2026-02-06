<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChartAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'number',
        'name',
        'type',
        'balance_minor',
    ];

    protected $casts = [
        'balance_minor' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
