<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'type',
        'category_id',
        'price_minor',
        'sale_price_minor',
        'currency',
        'duration',
        'active',
        'image',
        'tags',
    ];

    protected $casts = [
        'price_minor' => 'integer',
        'sale_price_minor' => 'integer',
        'duration' => 'integer',
        'active' => 'boolean',
        'tags' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
