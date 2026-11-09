<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolEmployee extends Model
{
    protected $fillable = [
        'school_id', 'employee_number', 'display_name', 'job_title',
        'department_label', 'location', 'school_department_id', 'manager_id',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(SchoolDepartment::class, 'school_department_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    public function reportees(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }
}
