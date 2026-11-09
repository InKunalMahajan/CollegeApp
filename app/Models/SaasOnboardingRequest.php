<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaasOnboardingRequest extends Model
{
    protected $fillable = [
        'requested_by', 'organization_name', 'organization_code', 'contact_email',
        'status', 'school_id', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
}
