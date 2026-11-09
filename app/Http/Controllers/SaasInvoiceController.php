<?php

namespace App\Http\Controllers;

use App\Models\SaasInvoice;
use App\Models\SaasSubscription;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaasInvoiceController extends Controller
{
    public function index(): View
    {
        $subscriptions = SaasSubscription::with(['school','plan'])->latest()->get();
        $invoices = SaasInvoice::with(['school','subscription.plan'])->latest()->paginate(20);
        return view('saas.invoices', compact('invoices','subscriptions'));
    }

    public function store(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'saas_subscription_id'=>['required','integer',Rule::exists('saas_subscriptions','id')],
            'amount_paise'=>['required','integer','min:0','max:100000000000'],
            'description'=>['nullable','string','max:2000'],
        ]);
        DB::transaction(function () use ($request,$data,$audit) {
            $subscription = SaasSubscription::whereKey($data['saas_subscription_id'])
                ->lockForUpdate()->firstOrFail();
            $invoice = SaasInvoice::create([
                'invoice_number'=>'DRAFT-'.strtoupper((string) \Illuminate\Support\Str::ulid()),
                'school_id'=>$subscription->school_id,
                'saas_subscription_id'=>$subscription->id,
                'amount_paise'=>$data['amount_paise'],
                'currency'=>'INR',
                'status'=>'draft',
                'description'=>$data['description'] ?? null,
            ]);
            $audit->record($subscription->school_id,$request->user()->id,
                'saas.invoice.draft_created','saas_invoice',$invoice->id);
        });
        return to_route('saas.invoices.index')->with('success','Draft invoice saved. No payment was collected.');
    }
}
