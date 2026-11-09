<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasDomain extends Model
{
    protected $fillable = ['school_id','hostname','status','verification_token'];
    protected $hidden = ['verification_token'];
}
