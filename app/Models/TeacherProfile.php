<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    protected $fillable = ['user_id', 'school_id', 'campus_id', 'employee_code', 'designation', 'joined_on'];

    protected function casts(): array
    {
        return ['joined_on' => 'date'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function campus(): BelongsTo { return $this->belongsTo(Campus::class); }
    public function subjectAssignments(): HasMany { return $this->hasMany(TeacherSubjectAssignment::class); }
    public function classTeacherAssignments(): HasMany { return $this->hasMany(ClassTeacherAssignment::class); }
}
