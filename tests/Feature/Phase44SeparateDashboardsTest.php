<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase44SeparateDashboardsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $email, string $role = 'staff'): User
    {
        return User::create([
            'name' => 'Dashboard User',
            'email' => $email,
            'role' => $role,
            'password' => 'secure-dashboard-password-123',
        ]);
    }

    public function test_website_owner_gets_platform_dashboard_with_platform_only_navigation(): void
    {
        $owner = $this->user('owner44@example.test', 'admin');
        WebsiteOwner::create(['user_id' => $owner->id]);
        School::create(['name' => 'TEACH', 'code' => 'TEACH']);

        $this->actingAs($owner)->get(route('dashboard'))
            ->assertRedirect(route('website-owner.dashboard'));

        $this->get(route('website-owner.dashboard'))
            ->assertOk()
            ->assertSee('Website Owner Dashboard')
            ->assertSee('Platform administration')
            ->assertSee('TEACH')
            ->assertSee('User accounts and roles')
            ->assertDontSee('Student records')
            ->assertDontSee('Institution setup');

        // Website ownership does NOT automatically grant TEACH privileges.
        $teach = School::where('code', 'TEACH')->firstOrFail();
        $this->get(route('school-dashboard.index', $teach))->assertForbidden();
    }

    public function test_organization_admin_gets_own_organization_dashboard_not_website_owner(): void
    {
        $teach = School::create(['name' => 'TEACH', 'code' => 'TEACH']);
        $other = School::create(['name' => 'OTHER', 'code' => 'OTHER']);
        $admin = $this->user('org44@example.test');
        SchoolMembership::create([
            'school_id' => $teach->id, 'user_id' => $admin->id, 'role' => 'school_admin',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertRedirect(route('school-dashboard.index', $teach));

        $this->get(route('school-dashboard.index', $teach))
            ->assertOk()
            ->assertSee('TEACH')
            ->assertSee('Organization dashboard')
            ->assertDontSee('Owner dashboard');

        $this->get(route('website-owner.dashboard'))->assertForbidden();
        $this->get(route('school-dashboard.index', $other))->assertForbidden();
    }

    public function test_campus_admin_goes_to_own_campus_and_cannot_open_organization_dashboard(): void
    {
        $teach = School::create(['name' => 'TEACH', 'code' => 'TEACH']);
        $mumbai = Campus::create(['school_id' => $teach->id, 'name' => 'Mumbai', 'code' => 'MUM']);
        $pune = Campus::create(['school_id' => $teach->id, 'name' => 'Pune', 'code' => 'PUN']);
        $admin = $this->user('mumbai44@example.test');
        CampusMembership::create([
            'campus_id' => $mumbai->id, 'user_id' => $admin->id, 'role' => 'campus_admin',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertRedirect(route('campus-workspace.index', [$teach, $mumbai]));

        $this->get(route('campus-workspace.index', [$teach, $mumbai]))->assertOk();
        $this->get(route('campus-workspace.index', [$teach, $pune]))->assertForbidden();
        $this->get(route('school-dashboard.index', $teach))->assertForbidden();
        $this->get(route('website-owner.dashboard'))->assertForbidden();
    }

    public function test_legacy_global_admin_without_memberships_has_no_owner_dashboard(): void
    {
        $legacy = $this->user('legacy44@example.test', 'admin');
        $this->actingAs($legacy)->get(route('website-owner.dashboard'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();
    }
}
