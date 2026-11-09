<?php

namespace Tests\Feature;

use App\Models\SaasTenant;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SaasTenantAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase45SaasTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_schools_are_not_reassigned_or_implicitly_shared(): void
    {
        $a = School::create(['name' => 'TEACH', 'code' => 'TEACH']);
        $b = School::create(['name' => 'OTHER', 'code' => 'OTHER']);
        $user = User::create(['name' => 'Org Admin', 'email' => 'tenant45@example.test', 'role' => 'staff', 'password' => 'password-long-45']);
        SchoolMembership::create(['school_id' => $a->id, 'user_id' => $user->id, 'role' => 'school_admin']);
        $access = app(SaasTenantAccess::class);

        $this->assertTrue($access->canManage($user, $a));
        $this->assertFalse($access->canManage($user, $b));
        $this->assertSame([$a->id], $access->scopeToSchool(SchoolMembership::query(), $a)->pluck('school_id')->all());
        $this->assertSame('active', $access->status($a));
        SaasTenant::create(['school_id' => $a->id, 'status' => 'suspended']);
        $this->assertSame('suspended', $access->status($a));
        $this->assertFalse($access->mayOnboard($user, $a));
        $this->assertDatabaseCount('schools', 2);
    }
}
