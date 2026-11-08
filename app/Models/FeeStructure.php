<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model {
    protected $fillable = ['class_section_id','name','amount_paise','due_date'];
    protected function casts(): array { return ['due_date'=>'date','amount_paise'=>'integer']; }
    public function classSection(): BelongsTo { return $this->belongsTo(ClassSection::class); }
    public function charges(): HasMany { return $this->hasMany(FeeCharge::class); }
}
