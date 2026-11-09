<?php

namespace Tests\Feature;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\School;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase47SaasPlansTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_create_plan_and_assign_non_charging_trial(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $owner = User::create(['name'=>'Owner','email'=>'owner47@example.test',
            'role'=>'staff','password'=>'long-password-47']);
        $other = User::create(['name'=>'Staff','email'=>'staff47@example.test',
            'role'=>'staff','password'=>'long-password-47']);
        $this->actingAs($other)->get(route('saas.plans.index'))->assertForbidden();
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $this->actingAs($owner)->post(route('saas.plans.store'),[
            'code'=>'FREE','name'=>'Free','price_paise'=>0,
        ])->assertRedirect();
        $plan = SaasPlan::where('code','FREE')->firstOrFail();
        $this->post(route('saas.plans.assign'),[
            'school_id'=>$school->id,'saas_plan_id'=>$plan->id,'starts_on'=>'2026-11-09',
        ])->assertRedirect();
        $this->assertDatabaseHas('saas_subscriptions',[
            'school_id'=>$school->id,'saas_plan_id'=>$plan->id,'status'=>'trialing',
        ]);
        $this->post(route('saas.plans.assign'),[
            'school_id'=>$school->id,'saas_plan_id'=>$plan->id,'starts_on'=>'2026-11-09',
        ])->assertSessionHasErrors('school_id');
        $this->assertSame(1, SaasSubscription::count());
    }
}
