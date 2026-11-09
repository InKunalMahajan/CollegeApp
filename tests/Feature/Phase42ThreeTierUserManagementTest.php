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

class Phase42ThreeTierUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $name, string $email, string $role = 'staff'): User
    {
        return User::create(['name'=>$name,'email'=>$email,'role'=>$role,'password'=>'very-secure-password-2026']);
    }

    public function test_only_explicit_website_owner_can_manage_platform_accounts(): void
    {
        $owner = $this->user('Owner','owner42@example.test');
        $globalAdmin = $this->user('Legacy Admin','admin42@example.test','admin');
        $this->actingAs($globalAdmin)
            ->get(route('website-owner.users.index'))->assertForbidden();

        WebsiteOwner::create(['user_id'=>$owner->id]);
        $this->actingAs($owner)->get(route('website-owner.users.index'))->assertOk();
        $this->post(route('website-owner.users.store'),[
            'name'=>'New Staff','email'=>'staff42@example.test','role'=>'staff',
            'password'=>'another-very-secure-password','password_confirmation'=>'another-very-secure-password',
        ])->assertRedirect();
        $staff = User::where('email','staff42@example.test')->firstOrFail();
        $this->delete(route('website-owner.users.destroy',$staff))->assertRedirect();
        $this->assertNotNull($staff->fresh()->disabled_at);
        $this->post(route('website-owner.users.restore',$staff))->assertRedirect();
        $this->assertNull($staff->fresh()->disabled_at);
        $this->delete(route('website-owner.users.destroy',$owner))->assertForbidden();
    }

    public function test_organization_admin_manages_viewers_without_becoming_website_owner(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $other = School::create(['name'=>'OTHER','code'=>'OTHER']);
        $admin = $this->user('School Admin','org42@example.test');
        $staff = $this->user('Staff','staff-org42@example.test');
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$admin->id,'role'=>'school_admin']);
        $this->actingAs($admin)->post(route('organization-users.store',$school),[
            'email'=>$staff->email,'role'=>'auditor',
        ])->assertRedirect();
        $member = SchoolMembership::where('school_id',$school->id)
            ->where('user_id',$staff->id)->firstOrFail();
        $this->put(route('organization-users.update',[$school,$member]),[
            'role'=>'organization_viewer',
        ])->assertRedirect();
        $this->assertSame('organization_viewer',$member->fresh()->role);
        $this->post(route('organization-users.store',$school),[
            'email'=>$staff->email,'role'=>'school_admin',
        ])->assertSessionHasErrors('role');
        $this->get(route('organization-users.index',$other))->assertForbidden();
        $this->get(route('website-owner.users.index'))->assertForbidden();
        $this->delete(route('organization-users.destroy',[$school,$member]))->assertRedirect();
        $this->assertDatabaseMissing('school_memberships',['id'=>$member->id]);
        $this->assertDatabaseHas('users',['id'=>$staff->id]);
    }

    public function test_campus_admin_can_crud_only_non_admin_access_to_own_campus(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $mumbai = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $pune = Campus::create(['school_id'=>$school->id,'name'=>'Pune','code'=>'PUN']);
        $admin = $this->user('Mumbai admin','camp42@example.test');
        $staff = $this->user('Staff','staff-campus42@example.test');
        CampusMembership::create(['campus_id'=>$mumbai->id,'user_id'=>$admin->id,'role'=>'campus_admin']);

        $this->actingAs($admin)->post(route('campus-users.store',[$school,$mumbai]),[
            'email'=>$staff->email,'role'=>'campus_member',
        ])->assertRedirect();
        $member = CampusMembership::where('campus_id',$mumbai->id)->where('user_id',$staff->id)->firstOrFail();
        $this->put(route('campus-users.update',[$school,$mumbai,$member]),[
            'role'=>'campus_viewer',
        ])->assertRedirect();
        $this->assertSame('campus_viewer',$member->fresh()->role);
        $this->put(route('campus-users.update',[$school,$mumbai,$member]),[
            'role'=>'campus_admin',
        ])->assertSessionHasErrors('role');
        $this->get(route('campus-users.index',[$school,$pune]))->assertForbidden();
        $this->delete(route('campus-users.destroy',[$school,$mumbai,$member]))->assertRedirect();
        $this->assertDatabaseMissing('campus_memberships',['id'=>$member->id]);
        $this->assertDatabaseHas('users',['id'=>$staff->id]);
    }

    public function test_disabled_user_cannot_log_in_and_current_session_expires(): void
    {
        $user = $this->user('Disabled','disabled42@example.test');
        $user->update(['disabled_at'=>now()]);
        $this->post(route('login.store'),[
            'email'=>$user->email,'password'=>'very-secure-password-2026',
        ])->assertSessionHasErrors('email');
        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
    }
}
