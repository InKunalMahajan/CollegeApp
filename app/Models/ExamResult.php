<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamResult extends Model
{
    protected $fillable = ['examination_id', 'class_enrollment_id', 'marks_obtained', 'status', 'remarks'];
    public function examination(): BelongsTo { return $this->belongsTo(Examination::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(ClassEnrollment::class, 'class_enrollment_id'); }
    public function getPercentageAttribute(): ?float
    {
        return $this->marks_obtained === null ? null :
            round(100 * $this->marks_obtained / $this->examination->max_marks, 2);
    }
    public function getGradeAttribute(): ?string
    {
        if ($this->status !== 'graded' || $this->marks_obtained === null) return null;
        if ($this->marks_obtained < $this->examination->pass_marks) return 'F';
        return match (true) {
            $this->percentage >= 90 => 'A+',
            $this->percentage >= 75 => 'A',
            $this->percentage >= 60 => 'B',
            $this->percentage >= 40 => 'C',
            default => 'D',
        };
    }
}
