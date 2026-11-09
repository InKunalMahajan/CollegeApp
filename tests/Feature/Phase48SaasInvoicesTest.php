<?php

namespace Tests\Feature;

use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\School;
use App\Models\User;
use App\Models\WebsiteOwner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase48SaasInvoicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creates_draft_only_and_non_owner_is_forbidden(): void
    {
        $school = School::create(['name'=>'TEACH','code'=>'TEACH']);
        $plan = SaasPlan::create(['code'=>'FREE','name'=>'Free','price_paise'=>0,'currency'=>'INR']);
        $subscription = SaasSubscription::create([
            'school_id'=>$school->id,'saas_plan_id'=>$plan->id,
            'starts_on'=>'2026-11-09','status'=>'trialing',
        ]);
        $owner = User::create(['name'=>'Owner','email'=>'owner48@example.test','role'=>'staff','password'=>'long-password-48']);
        $staff = User::create(['name'=>'Staff','email'=>'staff48@example.test','role'=>'staff','password'=>'long-password-48']);
        WebsiteOwner::create(['user_id'=>$owner->id]);
        $payload = ['saas_subscription_id'=>$subscription->id,'amount_paise'=>25000];

        $this->actingAs($staff)->post(route('saas.invoices.store'),$payload)->assertForbidden();
        $this->actingAs($owner)->post(route('saas.invoices.store'),$payload)->assertRedirect();
        $invoice = SaasInvoice::firstOrFail();
        $this->assertSame('draft',$invoice->status);
        $this->assertSame('INR',$invoice->currency);
        $this->assertSame(25000,$invoice->amount_paise);
        $this->assertNull($invoice->issued_on);
        $this->assertDatabaseHas('school_audit_events',['school_id'=>$school->id,'action'=>'saas.invoice.draft_created']);
    }
}
