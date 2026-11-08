<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassEnrollment extends Model
{
    protected $fillable = ['student_id', 'class_section_id'];
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function classSection(): BelongsTo { return $this->belongsTo(ClassSection::class); }
}
