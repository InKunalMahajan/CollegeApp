<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolAuditEvent extends Model
{
    public $timestamps = false;

    // Events are intentionally append-only via the service.
    // No update/delete API is exposed in the application.
    protected $fillable = ['school_id','actor_user_id','action','subject_type','subject_id','context'];
    protected function casts(): array { return ['context' => 'array', 'created_at' => 'datetime']; }
}
