<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendance extends Model
{
    protected $fillable = ['class_enrollment_id', 'attendance_date', 'status'];
    protected function casts(): array { return ['attendance_date' => 'date']; }
    public function enrollment(): BelongsTo { return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id'); }
}
