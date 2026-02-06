<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'type',
        'difficulty',
        'visibility',
        'duration_hours',
        'price_minor',
        'currency',
        'image',
        'certificate',
        'max_participants',
    ];

    protected $casts = [
        'duration_hours' => 'integer',
        'price_minor' => 'integer',
        'certificate' => 'boolean',
        'max_participants' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
