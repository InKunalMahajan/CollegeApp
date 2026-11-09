<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolEmployee extends Model
{
    use SoftDeletes;

    public const STATUSES = ['active', 'on_leave', 'inactive', 'separated'];

    public const TYPES = ['full_time', 'part_time', 'contract', 'intern'];

    protected function casts(): array
    {
        return ['joined_on' => 'date'];
    }

    protected $fillable = [
        'school_id', 'employee_number', 'display_name', 'job_title',
        'department_label', 'location', 'school_department_id', 'manager_id',
        'employment_status', 'employment_type', 'joined_on', 'work_email',
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
