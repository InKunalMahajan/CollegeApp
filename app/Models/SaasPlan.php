<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlan extends Model
{
    protected $fillable = ['code','name','price_paise','currency','max_campuses','max_active_students','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean']; }
}
