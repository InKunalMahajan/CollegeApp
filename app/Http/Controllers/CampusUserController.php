<?php

namespace App\Http\Controllers;

use App\Models\Campus;
use App\Models\CampusMembership;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampusUserController extends Controller
{
    private const MANAGED_ROLES = ['campus_member','campus_viewer'];

    public function index(School $school, Campus $campus): View
    {
        $memberships = CampusMembership::with('user')
            ->where('campus_id',$campus->id)->orderBy('role')->orderBy('id')->paginate(20);
        return view('campus-users.index',compact('school','campus','memberships'));
    }

    public function store(Request $request, School $school, Campus $campus, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'email'=>['required','email','max:255'],
            'role'=>['required',Rule::in(self::MANAGED_ROLES)],
        ]);
        $user = User::where('email',$data['email'])->whereNull('disabled_at')->first();
        if (!$user) throw ValidationException::withMessages(['email'=>'No eligible active existing account found.']);
        DB::transaction(function () use ($request,$school,$campus,$user,$data,$audit) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            // A user cannot silently gain operational access to two campuses
            // of the same organization through separate membership records.
            $other = CampusMembership::where('user_id',$user->id)
                ->whereHas('campus',fn ($q) => $q->where('school_id',$school->id))
                ->exists();
            if ($other) throw ValidationException::withMessages([
                'email'=>'This account already has campus access within the organization.',
            ]);
            $membership = CampusMembership::create([
                'campus_id'=>$campus->id,'user_id'=>$user->id,'role'=>$data['role'],
            ]);
            $audit->record($school->id,$request->user()->id,'campus.user.assigned',
                'campus_membership',$membership->id,['campus_id'=>$campus->id,'user_id'=>$user->id,'role'=>$data['role']]);
        });
        return to_route('campus-users.index',[$school,$campus])->with('success','Campus access assigned.');
    }

    public function update(Request $request, School $school, Campus $campus, CampusMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_unless((int)$membership->campus_id === (int)$campus->id
            && in_array($membership->role,self::MANAGED_ROLES,true),404);
        $data = $request->validate(['role'=>['required',Rule::in(self::MANAGED_ROLES)]]);
        DB::transaction(function () use ($request,$school,$campus,$membership,$data,$audit) {
            $record = CampusMembership::where('campus_id',$campus->id)
                ->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($record->role,self::MANAGED_ROLES,true),404);
            $record->update(['role'=>$data['role']]);
            $audit->record($school->id,$request->user()->id,'campus.user.updated',
                'campus_membership',$record->id,['campus_id'=>$campus->id,'role'=>$data['role']]);
        });
        return to_route('campus-users.index',[$school,$campus])->with('success','Campus access updated.');
    }

    public function destroy(Request $request, School $school, Campus $campus, CampusMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_unless((int)$membership->campus_id === (int)$campus->id
            && in_array($membership->role,self::MANAGED_ROLES,true),404);
        DB::transaction(function () use ($request,$school,$campus,$membership,$audit) {
            $record = CampusMembership::where('campus_id',$campus->id)
                ->whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($record->role,self::MANAGED_ROLES,true),404);
            $audit->record($school->id,$request->user()->id,'campus.user.revoked',
                'campus_membership',$record->id,['campus_id'=>$campus->id,'user_id'=>$record->user_id]);
            $record->delete();
        });
        return to_route('campus-users.index',[$school,$campus])->with('success','Campus access revoked; account retained.');
    }
}
