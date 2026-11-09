<?php

namespace Tests\Feature;

use App\Models\SaasBranding;
use App\Models\SaasDomain;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase49SaasBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_brand_only_own_organization_and_request_pending_domain(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $other = School::create(['name'=>'OTHER','code'=>'OTHER']);
        $admin = User::create(['name'=>'Org Admin','email'=>'org49@example.test',
            'role'=>'staff','password'=>'long-password-49']);
        SchoolMembership::create(['school_id'=>$school->id,'user_id'=>$admin->id,'role'=>'school_admin']);

        $this->actingAs($admin)->put(route('saas.branding.update',$school),[
            'display_name'=>'TEACH Campus Network', 'primary_color'=>'#3B2F8F',
        ])->assertRedirect();
        $this->assertDatabaseHas('saas_brandings',[
            'school_id'=>$school->id,'display_name'=>'TEACH Campus Network',
        ]);
        $this->post(route('saas.branding.domain',$school),[
            'hostname'=>'portal.teach.example',
        ])->assertRedirect();
        $this->assertDatabaseHas('saas_domains',[
            'school_id'=>$school->id,'hostname'=>'portal.teach.example','status'=>'pending',
        ]);
        $this->assertSame(1,SaasDomain::count());
        $this->put(route('saas.branding.update',$other),[
            'display_name'=>'Hacked','primary_color'=>'#FFFFFF',
        ])->assertForbidden();
        $this->assertDatabaseMissing('saas_brandings',['school_id'=>$other->id]);
    }
}
