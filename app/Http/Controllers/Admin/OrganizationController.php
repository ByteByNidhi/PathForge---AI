<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function index(): View
    {
        $organizations = Organization::query()
            ->withCount(['users', 'opportunities'])
            ->orderBy('name')
            ->get();

        return view('admin.organizations.index', [
            'organizations' => $organizations,
        ]);
    }

    public function create(): View
    {
        return view('admin.organizations.form');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'owner_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-z]+(?: [A-Za-z]+)*$/',
            ],
            'owner_email' => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ], [
            'owner_name.regex' => 'Use letters only, with spaces between words. Numbers and symbols are not allowed.',
        ]);

        $organization = Organization::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'website' => $validated['website'] ?? null,
            'description' => $validated['description'] ?? null,
            'status' => Organization::STATUS_ACTIVE,
        ]);

        $owner = User::query()->create([
            'name' => $validated['owner_name'],
            'email' => $validated['owner_email'],
            'password' => Hash::make($validated['owner_password']),
            'onboarding_completed' => true,
            'is_admin' => false,
        ]);

        $organization->users()->attach($owner->id, ['role' => Organization::ROLE_OWNER]);

        return redirect()
            ->route('admin.organizations.index')
            ->with('success', 'Organization and owner account created. They can log in with the owner email.');
    }
}
