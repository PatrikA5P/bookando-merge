<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'date',
        'account',
        'contra_account',
        'description',
        'debit_minor',
        'credit_minor',
        'reference',
    ];

    protected $casts = [
        'date' => 'date',
        'debit_minor' => 'integer',
        'credit_minor' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
