<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssignmentSubmission extends Model {
    protected $fillable = ['school_assignment_id','class_enrollment_id','response','submitted_at','marks','feedback','graded_at'];
    protected function casts(): array { return ['submitted_at'=>'datetime','graded_at'=>'datetime']; }
    public function assignment(): BelongsTo { return $this->belongsTo(SchoolAssignment::class,'school_assignment_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(ClassEnrollment::class,'class_enrollment_id'); }
}
