<?php

namespace App\Http\Controllers;

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\School;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaasPlanController extends Controller
{
    public function index(): View
    {
        $plans = SaasPlan::orderBy('name')->get();
        $schools = School::orderBy('name')->get();
        $subscriptions = SaasSubscription::with(['school','plan'])->latest()->paginate(25);
        return view('saas.plans', compact('plans','schools','subscriptions'));
    }

    public function store(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $request->merge(['code'=>strtoupper(trim((string)$request->input('code')))]);
        $data = $request->validate([
            'code'=>['required','string','max:60','regex:/^[A-Z0-9_-]+$/',Rule::unique('saas_plans','code')],
            'name'=>['required','string','max:120'],
            'price_paise'=>['required','integer','min:0','max:1000000000'],
            'max_campuses'=>['nullable','integer','min:1','max:100000'],
            'max_active_students'=>['nullable','integer','min:1','max:1000000'],
        ]);
        DB::transaction(function () use ($data,$request,$audit) {
            $plan = SaasPlan::create([...$data,'currency'=>'INR','is_active'=>true]);
            $audit->record(null,$request->user()->id,'saas.plan.created','saas_plan',$plan->id);
        });
        return to_route('saas.plans.index')->with('success','Plan created (no payment collection enabled).');
    }

    public function assign(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'school_id'=>['required','integer',Rule::exists('schools','id')],
            'saas_plan_id'=>['required','integer',Rule::exists('saas_plans','id')],
            'starts_on'=>['required','date_format:Y-m-d'],
            'ends_on'=>['nullable','date_format:Y-m-d','after_or_equal:starts_on'],
        ]);
        DB::transaction(function () use ($data,$request,$audit) {
            $school = School::whereKey($data['school_id'])->lockForUpdate()->firstOrFail();
            $plan = SaasPlan::whereKey($data['saas_plan_id'])->where('is_active',true)->firstOrFail();
            if (SaasSubscription::where('school_id',$school->id)
                ->whereIn('status',['trialing','active'])->exists()) {
                throw ValidationException::withMessages([
                    'school_id'=>'An active or trialing subscription already exists. Review it before creating another.',
                ]);
            }
            $subscription = SaasSubscription::create([
                ...$data, 'school_id'=>$school->id,'saas_plan_id'=>$plan->id,'status'=>'trialing',
            ]);
            $audit->record($school->id,$request->user()->id,'saas.subscription.trial_created',
                'saas_subscription',$subscription->id,['plan_id'=>$plan->id]);
        });
        return to_route('saas.plans.index')->with('success','Trial subscription recorded. No payment captured.');
    }
}
