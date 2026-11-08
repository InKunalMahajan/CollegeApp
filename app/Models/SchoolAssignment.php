<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolAssignment extends Model {
    protected $fillable = ['teacher_subject_assignment_id','title','instructions','due_at','max_marks','status'];
    protected function casts(): array { return ['due_at' => 'datetime']; }
    public function teachingAssignment(): BelongsTo { return $this->belongsTo(TeacherSubjectAssignment::class, 'teacher_subject_assignment_id'); }
    public function submissions(): HasMany { return $this->hasMany(AssignmentSubmission::class); }
}
