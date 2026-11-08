<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSection extends Model
{
    protected $fillable = ['campus_id','academic_year_id','name','code'];

    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
}
