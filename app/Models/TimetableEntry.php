<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    protected $fillable = ['class_section_id', 'teacher_profile_id', 'subject_id', 'day_of_week', 'starts_at', 'ends_at', 'room'];
    public function classSection(): BelongsTo { return $this->belongsTo(ClassSection::class); }
    public function teacherProfile(): BelongsTo { return $this->belongsTo(TeacherProfile::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
}
