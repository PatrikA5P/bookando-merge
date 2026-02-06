<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'position',
        'department',
        'status',
        'role',
        'hire_date',
        'exit_date',
        'avatar',
        'bio',
        'street',
        'zip',
        'city',
        'country',
        'salary_minor',
        'vacation_days_total',
        'vacation_days_used',
        'employment_percent',
        'social_security_number',
        'assigned_service_ids',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'exit_date' => 'date',
        'salary_minor' => 'integer',
        'vacation_days_total' => 'integer',
        'vacation_days_used' => 'integer',
        'employment_percent' => 'integer',
        'assigned_service_ids' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function salaryDeclarations(): HasMany
    {
        return $this->hasMany(SalaryDeclaration::class);
    }
}
