<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasPlatformAppearance extends Model
{
    protected $fillable = ['primary_color', 'font_family', 'font_size', 'density'];
}
