<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    protected $fillable = ['name','code','email'];

    public function campuses(): HasMany { return $this->hasMany(Campus::class); }
    public function academicYears(): HasMany { return $this->hasMany(AcademicYear::class); }
    public function subjects(): HasMany { return $this->hasMany(Subject::class); }
}
