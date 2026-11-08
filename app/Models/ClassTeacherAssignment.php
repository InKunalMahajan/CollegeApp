<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassTeacherAssignment extends Model
{
    protected $fillable = ['teacher_profile_id', 'class_section_id'];

    public function teacherProfile(): BelongsTo { return $this->belongsTo(TeacherProfile::class); }
    public function classSection(): BelongsTo { return $this->belongsTo(ClassSection::class); }
}
