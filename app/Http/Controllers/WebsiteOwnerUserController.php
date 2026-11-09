<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SchoolMembership;
use App\Models\User;
use App\Models\WebsiteOwner;
use App\Services\SchoolAuditTrail;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class WebsiteOwnerUserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q) => $q->where(
                fn ($query) => $query->where('name', 'like', '%'.addcslashes($request->input('search'), '%_\\').'%')
                    ->orWhere('email', 'like', '%'.addcslashes($request->input('search'), '%_\\').'%')
            ))->orderBy('name')->paginate(20)->withQueryString();
        $schools = School::orderBy('name')->get();
        $admins = SchoolMembership::with(['user', 'school'])
            ->where('role', 'school_admin')->orderBy('school_id')->get();
        $ownerIds = WebsiteOwner::pluck('user_id')->all();

        return view('website-owner.users', compact('users', 'schools', 'admins', 'ownerIds'));
    }

    public function store(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255',Rule::unique('users','email')],
            'password'=>['required','confirmed',Password::min(12)],
            'role'=>['required',Rule::in(['staff','teacher','student'])],
        ]);
        DB::transaction(function () use ($request,$data,$audit) {
            $user = User::create($data);
            $audit->record(null,$request->user()->id,'website.user.created','user',$user->id);
        });
        return to_route('website-owner.users.index')->with('success','Account created.');
    }

    public function update(Request $request, User $user, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_if(WebsiteOwner::where('user_id',$user->id)->exists(),403);
        $data = $request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],
            'password'=>['nullable','confirmed',Password::min(12)],
        ]);
        if (blank($data['password'] ?? null)) unset($data['password']);
        DB::transaction(function () use ($request,$user,$data,$audit) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if(WebsiteOwner::where('user_id',$locked->id)->exists(),403);
            $locked->fill($data);
            $fields = array_keys($locked->getDirty());
            if ($fields) {
                $locked->save();
                $audit->record(null,$request->user()->id,'website.user.updated','user',$locked->id,
                    ['fields'=>array_values(array_diff($fields,['password'])),'credential_changed'=>in_array('password',$fields,true)]);
            }
        });
        return to_route('website-owner.users.index')->with('success','Account updated.');
    }

    public function destroy(Request $request, User $user, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_if((int)$user->id === (int)$request->user()->id,403);
        DB::transaction(function () use ($request,$user,$audit) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if(WebsiteOwner::where('user_id',$locked->id)->exists(),403);
            if ($locked->disabled_at === null) {
                $locked->update(['disabled_at'=>now()]);
                $audit->record(null,$request->user()->id,'website.user.disabled','user',$locked->id);
            }
        });
        return to_route('website-owner.users.index')->with('success','Account disabled; historical records retained.');
    }

    public function restore(Request $request, User $user, SchoolAuditTrail $audit): RedirectResponse
    {
        DB::transaction(function () use ($request,$user,$audit) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            abort_if(WebsiteOwner::where('user_id',$locked->id)->exists(),403);
            if ($locked->disabled_at !== null) {
                $locked->update(['disabled_at'=>null]);
                $audit->record(null,$request->user()->id,'website.user.enabled','user',$locked->id);
            }
        });
        return to_route('website-owner.users.index')->with('success','Account re-enabled.');
    }

    public function grant(Request $request, SchoolAuditTrail $audit): RedirectResponse
    {
        $data = $request->validate([
            'school_id'=>['required','integer',Rule::exists('schools','id')],
            'email'=>['required','email','max:255'],
        ]);
        $user = User::where('email',$data['email'])->whereNull('disabled_at')->first();
        if (!$user) {
            throw ValidationException::withMessages(['email'=>'No eligible active existing account found.']);
        }
        DB::transaction(function () use ($request,$user,$data,$audit) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $membership = SchoolMembership::updateOrCreate(
                ['school_id'=>$data['school_id'],'user_id'=>$user->id],
                ['role'=>'school_admin']
            );
            $audit->record((int)$data['school_id'],$request->user()->id,
                'website.school_admin.granted','school_membership',$membership->id,['user_id'=>$user->id]);
        });
        return to_route('website-owner.users.index')->with('success','Organization administrator granted.');
    }

    public function revoke(Request $request, SchoolMembership $membership, SchoolAuditTrail $audit): RedirectResponse
    {
        abort_unless($membership->role === 'school_admin',404);
        DB::transaction(function () use ($request,$membership,$audit) {
            $locked = SchoolMembership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->role === 'school_admin',404);
            $audit->record($locked->school_id,$request->user()->id,
                'website.school_admin.revoked','school_membership',$locked->id,['user_id'=>$locked->user_id]);
            $locked->delete();
        });
        return to_route('website-owner.users.index')->with('success','Organization administrator access revoked.');
    }
}
