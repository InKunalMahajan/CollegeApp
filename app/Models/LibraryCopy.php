<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryCopy extends Model {
    protected $fillable = ['library_book_id','barcode','status'];
    public function book(): BelongsTo { return $this->belongsTo(LibraryBook::class,'library_book_id'); }
    public function loans(): HasMany { return $this->hasMany(LibraryLoan::class); }
}
