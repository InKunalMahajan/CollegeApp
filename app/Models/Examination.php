<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examination extends Model
{
    protected $fillable = ['class_section_id', 'subject_id', 'name', 'exam_date', 'max_marks', 'pass_marks'];
    protected function casts(): array { return ['exam_date' => 'date']; }
    public function classSection(): BelongsTo { return $this->belongsTo(ClassSection::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function results(): HasMany { return $this->hasMany(ExamResult::class); }
}
