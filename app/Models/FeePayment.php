<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model {
    protected $fillable = ['fee_charge_id','amount_paise','paid_on','method','reference','recorded_by'];
    protected function casts(): array { return ['paid_on'=>'date','amount_paise'=>'integer']; }
    public function charge(): BelongsTo { return $this->belongsTo(FeeCharge::class,'fee_charge_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); }
}
