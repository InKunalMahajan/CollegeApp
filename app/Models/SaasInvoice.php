<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasInvoice extends Model
{
    protected $fillable = ['invoice_number','school_id','saas_subscription_id','amount_paise','currency','status','issued_on','due_on','description'];
    protected function casts(): array { return ['issued_on'=>'date','due_on'=>'date']; }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(SaasSubscription::class,'saas_subscription_id'); }
}
