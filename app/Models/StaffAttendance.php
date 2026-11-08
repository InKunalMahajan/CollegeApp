<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendance extends Model
{
    protected $fillable = ['teacher_profile_id', 'attendance_date', 'status'];
    protected function casts(): array { return ['attendance_date' => 'date']; }
    public function teacherProfile(): BelongsTo { return $this->belongsTo(TeacherProfile::class); }
}
