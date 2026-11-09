<?php

namespace App\Http\Controllers;

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

class OrganizationUserController extends Controller
{
    private const MANAGED_ROLES = ['auditor','organization_viewer'];

    public function index(School $school): View
    {
        $memberships = SchoolMembership::with('user')
            ->where('school_id',$school->id)
            ->orderBy('role')->orderBy('id')->paginate(20);
        return view('organization-users.index',compact('school','memberships'));
    }

    public function store(Request $request, School $school, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'email'=>['required','email','max:255'],
            'role'=>['required',Rule::in(self::MANAGED_ROLES)],
        ]);
        $user = User::where('email',$data['email'])->whereNull('disabled_at')->first();
        if (!$user) throw ValidationException::withMessages(['email'=>'No eligible active existing account found.']);
        DB::transaction(function () use ($request,$school,$user,$data,$audit) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $already = SchoolMembership::where('school_id',$school->id)
                ->where('user_id',$user->id)->exists();
            if ($already) throw ValidationException::withMessages(['email'=>'Account already has an organization membership.']);
            $membership = SchoolMembership::create([
                'school_id'=>$school->id,'user_id'=>$user->id,'role'=>$data['role'],
            ]);
            $audit->record($school->id,$request->user()->id,
                'organization.user.assigned','school_membership',$membership->id,
                ['role'=>$data['role'],'user_id'=>$user->id]);
        });
        return to_route('organization-users.index',$school)->with('success','Organization access assigned.');
    }

    public function update(Request $request, School $school, SchoolMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_unless((int)$membership->school_id === (int)$school->id
            && in_array($membership->role,self::MANAGED_ROLES,true),404);
        $data = $request->validate(['role'=>['required',Rule::in(self::MANAGED_ROLES)]]);
        DB::transaction(function () use ($request,$school,$membership,$data,$audit) {
            $record = SchoolMembership::where('school_id',$school->id)
                ->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($record->role,self::MANAGED_ROLES,true),404);
            $record->update(['role'=>$data['role']]);
            $audit->record($school->id,$request->user()->id,
                'organization.user.updated','school_membership',$record->id,['role'=>$data['role']]);
        });
        return to_route('organization-users.index',$school)->with('success','Organization access updated.');
    }

    public function destroy(Request $request, School $school, SchoolMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_unless((int)$membership->school_id === (int)$school->id
            && in_array($membership->role,self::MANAGED_ROLES,true),404);
        DB::transaction(function () use ($request,$school,$membership,$audit) {
            $record = SchoolMembership::where('school_id',$school->id)
                ->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($record->role,self::MANAGED_ROLES,true),404);
            $audit->record($school->id,$request->user()->id,'organization.user.revoked',
                'school_membership',$record->id,['user_id'=>$record->user_id]);
            $record->delete();
        });
        return to_route('organization-users.index',$school)->with('success','Organization access revoked; account retained.');
    }
}
