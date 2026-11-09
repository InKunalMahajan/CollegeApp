<?php

namespace Tests\Feature;

use App\Models\SaasDomain;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase50SaasSecurityGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_see_saas_release_page_and_drafts(): void
    {
        $owner = User::create(['name'=>'Owner','email'=>'owner50@example.test',
            'role'=>'staff','password'=>'long-password-50']);
        $admin = User::create(['name'=>'Admin','email'=>'org50@example.test',
            'role'=>'staff','password'=>'long-password-50']);
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        WebsiteOwner::create(['user_id'=>$owner->id]);
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$admin->id,'role'=>'school_admin']);

        $this->actingAs($admin)->get(route('saas.release.index'))->assertForbidden();
        $this->get(route('saas.invoices.index'))->assertForbidden();
        $this->get(route('saas.plans.index'))->assertForbidden();

        $this->actingAs($owner)->get(route('saas.release.index'))
            ->assertOk()->assertSee('SaaS Release Checks')
            ->assertSee('Custom domains are pending only')
            ->assertSee('saas_tenants');
    }

    public function test_pending_custom_domain_does_not_create_global_tenant_access(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $other = School::create(['name'=>'OTHER','code'=>'OTHER']);
        $a = User::create(['name'=>'Admin','email'=>'school50@example.test',
            'role'=>'staff','password'=>'long-password-50']);
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$a->id,'role'=>'school_admin']);
        SaasDomain::create([
            'school_id'=>$school->id,'hostname'=>'school.example',
            'status'=>'pending','verification_token'=>bin2hex(random_bytes(24)),
        ]);

        $this->actingAs($a)->withServerVariables(['HTTP_HOST'=>'school.example'])
            ->get(route('school-dashboard.index',$other))->assertForbidden();
        $this->get(route('saas.branding.index',$other))->assertForbidden();
    }
}
