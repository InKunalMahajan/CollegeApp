<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';
    protected $fillable = ['curriculum_lesson_id', 'teacher_subject_assignment_id', 'status', 'completed_on', 'remarks'];
    protected function casts(): array { return ['completed_on' => 'date']; }
    public function lesson(): BelongsTo { return $this->belongsTo(CurriculumLesson::class, 'curriculum_lesson_id'); }
    public function assignment(): BelongsTo { return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id'); }
}
