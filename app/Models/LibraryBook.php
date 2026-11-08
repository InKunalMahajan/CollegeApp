<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryBook extends Model {
    protected $fillable = ['school_id','title','author','isbn'];
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function copies(): HasMany { return $this->hasMany(LibraryCopy::class); }
}
