<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolDepartment extends Model
{
    protected $fillable = ['school_id', 'name', 'code', 'description'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SchoolDepartmentMembership::class);
    }
}
