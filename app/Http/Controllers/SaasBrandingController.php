<?php

namespace App\Http\Controllers;

use App\Models\SaasBranding;
use App\Models\SaasDomain;
use App\Models\School;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SaasBrandingController extends Controller
{
    public function index(School $school): View
    {
        $branding = SaasBranding::where('school_id',$school->id)->first();
        $domains = SaasDomain::where('school_id',$school->id)->orderBy('hostname')->get();
        return view('saas.branding',compact('school','branding','domains'));
    }

    public function update(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'display_name'=>['required','string','max:120'],
            'primary_color'=>['required','regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        DB::transaction(function () use ($request,$school,$data,$audit) {
            $branding = SaasBranding::updateOrCreate(['school_id'=>$school->id],$data);
            $audit->record($school->id,$request->user()->id,
                'saas.branding.saved','saas_branding',$branding->id);
        });
        return to_route('saas.branding.index',$school)->with('success','Organization branding saved.');
    }

    public function requestDomain(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'hostname'=>['required','string','max:253',
                'regex:/^(?=.{4,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,63}$/',
                Rule::unique('saas_domains','hostname')],
        ]);
        $hostname = strtolower($data['hostname']);
        abort_if(in_array($hostname,['localhost','example.com','www.example.com'],true),422);
        DB::transaction(function () use ($request,$school,$hostname,$audit) {
            $domain = SaasDomain::create([
                'school_id'=>$school->id,'hostname'=>$hostname,'status'=>'pending',
                'verification_token'=>bin2hex(random_bytes(24)),
            ]);
            $audit->record($school->id,$request->user()->id,
                'saas.domain.requested','saas_domain',$domain->id,['hostname'=>$hostname]);
        });
        return to_route('saas.branding.index',$school)->with('success',
            'Domain requested. It is not activated or routed until ownership verification is implemented.');
    }
}
