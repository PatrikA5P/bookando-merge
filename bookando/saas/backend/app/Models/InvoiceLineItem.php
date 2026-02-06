<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price_minor',
        'total_minor',
        'vat_rate_percent',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price_minor' => 'integer',
        'total_minor' => 'integer',
        'vat_rate_percent' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
