<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\SaasOnboardingRequest;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase46SaasOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_approval_creates_organization_only_after_review(): void
    {
        $applicant = User::create(['name'=>'Applicant','email'=>'applicant46@example.test',
            'password'=>'secure-password-46','role'=>'staff']);
        $owner = User::create(['name'=>'Owner','email'=>'owner46@example.test',
            'password'=>'secure-password-46','role'=>'staff']);
        WebsiteOwner::create(['user_id'=>$owner->id]);

        $this->actingAs($applicant)->post(route('saas.onboarding.store'),[
            'organization_name'=>'NEW SCHOOL','organization_code'=>'NEW_SCHOOL',
            'contact_email'=>'office46@example.test',
        ])->assertRedirect();
        $this->assertDatabaseCount('schools',0);
        $request = SaasOnboardingRequest::firstOrFail();
        $this->post(route('saas.onboarding.approve',$request))->assertForbidden();
        $this->actingAs($owner)->post(route('saas.onboarding.approve',$request))->assertRedirect();
        $school = School::where('code','NEW_SCHOOL')->firstOrFail();
        $this->assertDatabaseHas('saas_tenants',['school_id'=>$school->id,'status'=>'active']);
        $this->assertDatabaseHas('school_memberships',[
            'school_id'=>$school->id,'user_id'=>$applicant->id,'role'=>'school_admin',
        ]);
        $this->post(route('saas.onboarding.approve',$request))->assertStatus(409);
        $this->assertSame(1,SchoolMembership::where('school_id',$school->id)->count());
    }
}
