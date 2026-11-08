<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumLesson extends Model
{
    protected $fillable = ['curriculum_unit_id', 'title', 'sequence', 'estimated_minutes', 'objective'];
    public function unit(): BelongsTo { return $this->belongsTo(CurriculumUnit::class, 'curriculum_unit_id'); }
    public function progress(): HasMany { return $this->hasMany(LessonProgress::class); }
}
