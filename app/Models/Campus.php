<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campus extends Model
{
    protected $fillable = ['school_id','name','code','city'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function classSections(): HasMany { return $this->hasMany(ClassSection::class); }
}
