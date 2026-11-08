<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibraryLoan extends Model {
    protected $fillable = ['library_copy_id','class_enrollment_id','issued_on','due_on','returned_on','issued_by','returned_by'];
    protected function casts(): array { return ['issued_on'=>'date','due_on'=>'date','returned_on'=>'date']; }
    public function copy(): BelongsTo { return $this->belongsTo(LibraryCopy::class,'library_copy_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(ClassEnrollment::class,'class_enrollment_id'); }
}
