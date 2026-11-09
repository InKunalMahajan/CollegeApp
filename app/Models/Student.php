<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    protected $fillable = ['school_id', 'campus_id', 'admission_number', 'first_name', 'last_name', 'email', 'admission_date', 'course_id', 'status'];

    protected function casts(): array { return ['admission_date' => 'date']; }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
