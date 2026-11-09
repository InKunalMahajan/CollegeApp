<?php

namespace Tests\Feature;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase41CampusReleaseGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_new_campus_workspaces_reject_sibling_campus_and_unrelated_school(): void
    {
        $teach = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $other = School::create(['name'=>'OTHER','code'=>'OTHER']);
        $mumbai = Campus::create(['school_id'=>$teach->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$teach->id,'name'=>'Pune','code'=>'PUN']);
        $foreign = Campus::create(['school_id'=>$other->id,'name'=>'Delhi','code'=>'DEL']);
        $admin = User::create(['name'=>'Mumbai Admin','email'=>'m41@example.test','password'=>'password123','role'=>'staff']);
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);

        $routes = [
            'campus.teaching.index',
            'campus.attendance.index',
            'campus.exams.index',
            'campus.finance-library.index',
        ];
        foreach ($routes as $name) {
            $this->actingAs($admin)->get(route($name,[$teach,$mumbai]))->assertOk();
            $this->get(route($name,[$teach,$pune]))->assertForbidden();
            $this->get(route($name,[$other,$foreign]))->assertForbidden();
            $this->get(route($name,[$teach,$foreign]))->assertNotFound();
        }

        // An unrelated global role must never override campus membership.
        $outsider = User::create([
            'name'=>'Global Admin','email'=>'global41@example.test',
            'password'=>'password123','role'=>'admin',
        ]);
        foreach ($routes as $name) {
            $this->actingAs($outsider)->get(route($name,[$teach,$mumbai]))->assertForbidden();
        }
    }

    public function test_organization_admin_can_view_all_own_campus_workspaces(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $head = User::create(['name'=>'Head Office','email'=>'head41@example.test','password'=>'password123','role'=>'staff']);
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$head->id,'role'=>'school_admin']);
        foreach (['campus.teaching.index','campus.attendance.index','campus.exams.index','campus.finance-library.index'] as $name) {
            $this->actingAs($head)->get(route($name,[$school,$mumbai]))->assertOk();
            $this->get(route($name,[$school,$pune]))->assertOk();
        }
    }
}
