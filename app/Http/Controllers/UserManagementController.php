<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WebsiteOwner;
use App\Services\SchoolAuditTrail;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::query()->orderBy('name')->paginate(15),
            'ownerIds' => WebsiteOwner::query()->pluck('user_id')->all(),
        ]);
    }

    public function create(): View
    {
        return view('users.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['staff', 'teacher', 'student'])],
            'password' => ['required', 'confirmed', Password::min(12)],
        ]);

        DB::transaction(function () use ($request, $validated) {
            $user = User::create($validated);
            app(SchoolAuditTrail::class)->record(null, $request->user()->id,
                'website.user.created', 'user', $user->id);
        });

        return to_route('users.index')->with('success', 'User account created successfully.');
    }
}
