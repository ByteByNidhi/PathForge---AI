<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(): View
    {
        $organization = $this->organization();
        $this->authorize('view', $organization);

        return view('organization.members', [
            'organization' => $organization,
            'members' => $organization->users()->orderBy('name')->get(),
            'isOwner' => request()->user()->isOrganizationOwner($organization),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $this->organization();
        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'role' => ['required', Rule::in([Organization::ROLE_OWNER, Organization::ROLE_MEMBER])],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();

        if ($user->isAdmin()) {
            return back()->withErrors(['email' => 'Admin accounts cannot be added as organization members.']);
        }

        if ($organization->isMember($user)) {
            return back()->withErrors(['email' => 'That user is already a member of this organization.']);
        }

        $organization->users()->attach($user->id, ['role' => $validated['role']]);

        return redirect()
            ->route('organization.members.index')
            ->with('success', 'Member added.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $organization = $this->organization();
        $this->authorize('manageMembers', $organization);

        $validated = $request->validate([
            'role' => ['required', Rule::in([Organization::ROLE_OWNER, Organization::ROLE_MEMBER])],
        ]);

        abort_unless($organization->isMember($user), 403);

        if (
            $organization->isOwner($user)
            && $validated['role'] !== Organization::ROLE_OWNER
            && $organization->ownerCount() <= 1
        ) {
            return back()->withErrors(['role' => 'The organization must keep at least one owner.']);
        }

        $organization->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        return redirect()
            ->route('organization.members.index')
            ->with('success', 'Member role updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $organization = $this->organization();
        $this->authorize('manageMembers', $organization);

        abort_unless($organization->isMember($user), 403);

        if ($organization->isOwner($user) && $organization->ownerCount() <= 1) {
            return back()->withErrors(['email' => 'The last owner cannot be removed.']);
        }

        $organization->users()->detach($user->id);

        return redirect()
            ->route('organization.members.index')
            ->with('success', 'Member removed.');
    }

    private function organization(): Organization
    {
        $organization = request()->user()->currentOrganization();
        abort_unless($organization, 403);

        return $organization;
    }
}
