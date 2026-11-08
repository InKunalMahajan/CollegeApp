<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumUnit extends Model
{
    protected $fillable = ['subject_id', 'title', 'sequence', 'learning_outcome'];
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function lessons(): HasMany { return $this->hasMany(CurriculumLesson::class); }
}
