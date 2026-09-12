<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        $organization = $this->organization();
        $this->authorize('view', $organization);

        return view('organization.profile', [
            'organization' => $organization,
            'isOwner' => request()->user()->isOrganizationOwner($organization),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $this->organization();
        $this->authorize('update', $organization);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $organization->update($validated);

        $organization->opportunities()
            ->whereNotNull('organization_id')
            ->update(['organization' => $validated['name']]);

        return redirect()
            ->route('organization.profile.edit')
            ->with('success', 'Organization profile updated.');
    }

    private function organization(): Organization
    {
        $organization = request()->user()->currentOrganization();
        abort_unless($organization, 403);

        return $organization;
    }
}
