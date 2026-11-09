<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase43UserRoleCrudTest extends TestCase
{
    use RefreshDatabase;

    private function account(string $email, string $role = 'staff'): User
    {
        return User::create([
            'name'=>'Test User','email'=>$email,
            'role'=>$role,'password'=>'safe-test-password-123',
        ]);
    }

    public function test_website_owner_can_change_basic_user_role_on_users_screen(): void
    {
        $owner = $this->account('owner43@example.test', 'admin');
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $target = $this->account('target43@example.test', 'teacher');

        $this->actingAs($owner)->get(route('users.index'))
            ->assertOk()->assertSee('Edit role / account')->assertSee('Teacher');

        $this->put(route('website-owner.users.update',$target),[
            'name'=>'Updated Name',
            'email'=>$target->email,
            'role'=>'student',
        ])->assertRedirect();

        $this->assertDatabaseHas('users',[
            'id'=>$target->id,'name'=>'Updated Name','role'=>'student',
        ]);
        $this->assertDatabaseHas('school_audit_events',[
            'action'=>'website.user.updated','subject_type'=>'user','subject_id'=>$target->id,
        ]);
    }

    public function test_owner_cannot_assign_legacy_admin_role_using_user_edit_or_create(): void
    {
        $owner = $this->account('owner43@example.test','admin');
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $target = $this->account('target43@example.test','teacher');
        $this->actingAs($owner)->put(route('website-owner.users.update',$target),[
            'name'=>$target->name,'email'=>$target->email,'role'=>'admin',
        ])->assertSessionHasErrors('role');
        $this->assertSame('teacher',$target->fresh()->role);
        $this->post(route('users.store'),[
            'name'=>'Backdoor Admin','email'=>'backdoor43@example.test',
            'role'=>'admin','password'=>'safe-test-password-123',
            'password_confirmation'=>'safe-test-password-123',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users',['email'=>'backdoor43@example.test']);
    }

    public function test_owner_identity_is_protected_and_organization_or_campus_admin_cannot_edit_roles(): void
    {
        $owner = $this->account('owner43@example.test','admin');
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $target = $this->account('target43@example.test','teacher');
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $campus = Campus::create(['school_id'=>$school->id,'name'=>'Mumbai','code'=>'MUM']);
        $orgAdmin = $this->account('org43@example.test','staff');
        $campusAdmin = $this->account('camp43@example.test','staff');
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$orgAdmin->id,'role'=>'school_admin']);
        CampusMembership::create(['campus_id'=>$campus->id,'user_id'=>$campusAdmin->id,'role'=>'campus_admin']);

        $payload=['name'=>$target->name,'email'=>$target->email,'role'=>'staff'];
        foreach ([$orgAdmin,$campusAdmin] as $person) {
            $this->actingAs($person)
                ->put(route('website-owner.users.update',$target),$payload)
                ->assertForbidden();
            $this->get(route('users.index'))->assertForbidden();
        }

        $this->actingAs($owner)->put(route('website-owner.users.update',$owner),[
            'name'=>'Self Escalation','email'=>$owner->email,'role'=>'staff',
        ])->assertForbidden();
        $this->assertSame('admin',$owner->fresh()->role);
        $this->assertSame('teacher',$target->fresh()->role);
    }

    public function test_existing_non_owner_legacy_admin_can_be_downgraded_by_website_owner(): void
    {
        $owner = $this->account('owner43@example.test','admin');
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $legacy = $this->account('legacy43@example.test','admin');
        $this->actingAs($owner)->put(route('website-owner.users.update',$legacy),[
            'name'=>$legacy->name,'email'=>$legacy->email,'role'=>'staff',
        ])->assertRedirect();
        $this->assertSame('staff',$legacy->fresh()->role);
    }
}
