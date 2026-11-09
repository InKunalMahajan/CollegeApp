<?php

namespace App\Http\Controllers;

use App\Models\SaasOnboardingRequest;
use App\Models\SaasTenant;
use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SaasOnboardingController extends Controller
{
    public function index(Request $request): View
    {
        $requests = SaasOnboardingRequest::where('requested_by', $request->user()->id)
            ->latest()->paginate(15);

        return view('saas.onboarding', compact('requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['organization_code' => strtoupper(trim((string) $request->input('organization_code')))]);
        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/'],
            'contact_email' => ['required', 'email', 'max:255'],
        ]);

        if (SaasOnboardingRequest::where('requested_by', $request->user()->id)->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['organization_name' => 'You already have a pending organization request.']);
        }

        SaasOnboardingRequest::create([...$data, 'requested_by' => $request->user()->id]);

        return to_route('saas.onboarding.index')->with('success', 'Organization request submitted for review.');
    }

    public function review(): View
    {
        $requests = SaasOnboardingRequest::where('status', 'pending')
            ->orderBy('id')->paginate(20);
        return view('saas.review', compact('requests'));
    }

    public function approve(Request $request, SaasOnboardingRequest $onboarding, SchoolAuditTrail $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $onboarding, $audit) {
            $record = SaasOnboardingRequest::whereKey($onboarding->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending', 409);
            $applicant = User::whereKey($record->requested_by)->lockForUpdate()->firstOrFail();
            if ($applicant->disabled_at !== null || School::where('code', $record->organization_code)->exists()) {
                throw ValidationException::withMessages(['organization_code' => 'Account is ineligible or organization code already exists.']);
            }
            $school = School::create([
                'name' => $record->organization_name, 'code' => $record->organization_code,
                'email' => $record->contact_email,
            ]);
            SaasTenant::create(['school_id' => $school->id, 'status' => 'active', 'activated_at' => now()]);
            SchoolMembership::create(['school_id' => $school->id, 'user_id' => $applicant->id, 'role' => 'school_admin']);
            $record->update([
                'status' => 'approved', 'school_id' => $school->id,
                'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
            ]);
            $audit->record($school->id, $request->user()->id, 'saas.organization.approved',
                'school', $school->id, ['onboarding_request_id' => $record->id]);
        });

        return to_route('saas.onboarding.review')->with('success', 'Organization created and administrator assigned.');
    }

    public function reject(Request $request, SaasOnboardingRequest $onboarding): RedirectResponse
    {
        DB::transaction(function () use ($request, $onboarding) {
            $record = SaasOnboardingRequest::whereKey($onboarding->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending', 409);
            $record->update([
                'status' => 'rejected', 'reviewed_by' => $request->user()->id, 'reviewed_at' => now(),
            ]);
        });
        return to_route('saas.onboarding.review')->with('success', 'Request rejected.');
    }
}
