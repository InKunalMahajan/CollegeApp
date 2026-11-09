<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolDepartmentMembership extends Model
{
    public const ROLES = ['department_head', 'department_staff', 'department_viewer'];

    protected $fillable = ['school_id', 'school_department_id', 'user_id', 'role'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(SchoolDepartment::class, 'school_department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
