<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCharge extends Model {
    protected $fillable = ['fee_structure_id','class_enrollment_id','amount_paise'];
    protected function casts(): array { return ['amount_paise'=>'integer']; }
    public function structure(): BelongsTo { return $this->belongsTo(FeeStructure::class,'fee_structure_id'); }
    public function enrollment(): BelongsTo { return $this->belongsTo(ClassEnrollment::class,'class_enrollment_id'); }
    public function payments(): HasMany { return $this->hasMany(FeePayment::class); }
}
