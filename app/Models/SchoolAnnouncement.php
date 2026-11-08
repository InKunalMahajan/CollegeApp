<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolAnnouncement extends Model {
    protected $fillable = ['author_id','title','body','audience','published_at'];
    protected function casts(): array { return ['published_at'=>'datetime']; }
    public function author(): BelongsTo { return $this->belongsTo(User::class,'author_id'); }
}
