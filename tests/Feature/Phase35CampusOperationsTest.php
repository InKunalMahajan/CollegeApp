<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase35CampusOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function school(string $name): School
    {
        return School::create(['name' => $name, 'code' => $name]);
    }

    private function campus(School $school, string $city): Campus
    {
        return Campus::create([
            'school_id' => $school->id,
            'name' => $school->name.' '.$city,
            'code' => strtoupper($city),
            'city' => $city,
        ]);
    }

    private function account(string $name, string $role = 'staff'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).'@phase35.test',
            'password' => 'campus-operations-test-password',
            'role' => $role,
        ]);
    }

    public function test_campus_admin_can_view_only_assigned_campus_overview(): void
    {
        $school = $this->school('TEACH');
        $mumbai = $this->campus($school, 'Mumbai');
        $pune = $this->campus($school, 'Pune');
        $admin = $this->account('MumbaiAdmin');

        CampusMembership::create([
            'campus_id' => $mumbai->id,
            'user_id' => $admin->id,
            'role' => 'campus_admin',
        ]);

        $this->actingAs($admin)
            ->get(route('campus.operations.index', [$school, $mumbai]))
            ->assertOk()
            ->assertSee('Verified student enrollments')
            ->assertSee('Read-only');

        $this->get(route('campus.operations.index', [$school, $pune]))
            ->assertForbidden();
        $this->get(route('school-academics.index', $school))->assertForbidden();
        $this->get(route('school-finance.fees', $school))->assertForbidden();
        $this->get(route('school-finance.library', $school))->assertForbidden();
    }

    public function test_organization_admin_can_view_all_own_campuses_but_not_other_school(): void
    {
        $teach = $this->school('TEACH');
        $other = $this->school('OTHER');
        $mumbai = $this->campus($teach, 'Mumbai');
        $pune = $this->campus($teach, 'Pune');
        $foreign = $this->campus($other, 'Delhi');
        $admin = $this->account('HeadOffice');

        SchoolMembership::create([
            'school_id' => $teach->id,
            'user_id' => $admin->id,
            'role' => 'school_admin',
        ]);

        $this->actingAs($admin)
            ->get(route('campus.operations.index', [$teach, $mumbai]))->assertOk();
        $this->get(route('campus.operations.index', [$teach, $pune]))->assertOk();
        $this->get(route('campus.operations.index', [$other, $foreign]))->assertForbidden();
        $this->get(route('campus.operations.index', [$teach, $foreign]))->assertNotFound();
    }

    public function test_global_admin_without_membership_cannot_read_campus_operations(): void
    {
        $school = $this->school('TEACH');
        $campus = $this->campus($school, 'Mumbai');
        $globalAdmin = $this->account('GlobalAdmin', 'admin');

        $this->actingAs($globalAdmin)
            ->get(route('campus.operations.index', [$school, $campus]))
            ->assertForbidden();
    }
}
