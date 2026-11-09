<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaasSubscription extends Model
{
    protected $fillable = ['school_id','saas_plan_id','status','starts_on','ends_on'];
    protected function casts(): array { return ['starts_on'=>'date','ends_on'=>'date']; }
    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function plan(): BelongsTo { return $this->belongsTo(SaasPlan::class,'saas_plan_id'); }
}
