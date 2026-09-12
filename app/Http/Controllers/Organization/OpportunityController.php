<?php

namespace App\Http\Controllers\Organization;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OpportunityController as HubOpportunityController;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(): View
    {
        $organization = $this->organization();
        $this->authorize('view', $organization);

        $opportunities = $organization->opportunities()
            ->with('skills')
            ->orderByDesc('id')
            ->get();

        return view('organization.opportunities.index', [
            'organization' => $organization,
            'opportunities' => $opportunities,
            'isOwner' => request()->user()->isOrganizationOwner($organization),
        ]);
    }

    public function create(): View
    {
        $organization = $this->organization();
        $this->authorize('createOpportunity', $organization);

        return view('organization.opportunities.form', [
            'organization' => $organization,
            'opportunity' => new Opportunity,
            'types' => HubOpportunityController::TYPES,
            'skills' => Skill::query()->orderBy('name')->get(),
            'selectedSkillIds' => old('skill_ids', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $this->organization();
        $this->authorize('createOpportunity', $organization);

        $validated = $this->validated($request);
        $intent = $this->intent($request);
        $skillIds = $this->uniqueSkillIds($validated['skill_ids']);

        $opportunity = $organization->opportunities()->create([
            'title' => $validated['title'],
            'organization' => $organization->name,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'required_skills' => $this->skillNames($skillIds),
            'eligibility' => $validated['eligibility'] ?? null,
            'deadline' => $validated['deadline'] ?: null,
            'application_url' => $validated['application_url'] ?? null,
            'location' => $validated['location'] ?? null,
            'source' => Opportunity::SOURCE_ORGANIZATION,
            'approval_status' => $intent === 'submit'
                ? Opportunity::APPROVAL_PENDING
                : Opportunity::APPROVAL_DRAFT,
            'submitted_by_user_id' => $request->user()->id,
            'rejection_reason' => null,
        ]);

        $opportunity->skills()->sync($skillIds);

        $message = $intent === 'submit'
            ? 'Opportunity submitted for admin review.'
            : 'Draft opportunity saved.';

        return redirect()
            ->route('organization.opportunities.index')
            ->with('success', $message);
    }

    public function show(Opportunity $opportunity): View
    {
        $this->authorize('viewOrganization', $opportunity);
        $opportunity->loadMissing('skills');

        return view('organization.opportunities.show', [
            'organization' => $this->organization(),
            'opportunity' => $opportunity,
            'isOwner' => request()->user()->isOrganizationOwner($this->organization()),
        ]);
    }

    public function edit(Opportunity $opportunity): View
    {
        $this->authorize('updateOrganization', $opportunity);
        $opportunity->loadMissing('skills');

        return view('organization.opportunities.form', [
            'organization' => $this->organization(),
            'opportunity' => $opportunity,
            'types' => HubOpportunityController::TYPES,
            'skills' => Skill::query()->orderBy('name')->get(),
            'selectedSkillIds' => old('skill_ids', $opportunity->skills->pluck('id')->all()),
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('updateOrganization', $opportunity);

        $validated = $this->validated($request);
        $skillIds = $this->uniqueSkillIds($validated['skill_ids']);
        $intent = $this->intent($request);
        $organization = $this->organization();

        $status = $opportunity->approval_status;
        $rejectionReason = $opportunity->rejection_reason;

        if ($intent === 'submit' && ($opportunity->isDraft() || $opportunity->isRejected())) {
            $status = Opportunity::APPROVAL_PENDING;
            $rejectionReason = null;
        }

        $opportunity->update([
            'title' => $validated['title'],
            'organization' => $organization->name,
            'type' => $validated['type'],
            'description' => $validated['description'],
            'required_skills' => $this->skillNames($skillIds),
            'eligibility' => $validated['eligibility'] ?? null,
            'deadline' => $validated['deadline'] ?: null,
            'application_url' => $validated['application_url'] ?? null,
            'location' => $validated['location'] ?? null,
            'source' => Opportunity::SOURCE_ORGANIZATION,
            'organization_id' => $organization->id,
            'approval_status' => $status,
            'rejection_reason' => $rejectionReason,
        ]);

        $opportunity->skills()->sync($skillIds);

        return redirect()
            ->route('organization.opportunities.index')
            ->with('success', $intent === 'submit'
                ? 'Opportunity submitted for admin review.'
                : 'Opportunity updated.');
    }

    public function submit(Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('submitOrganization', $opportunity);

        $opportunity->update([
            'approval_status' => Opportunity::APPROVAL_PENDING,
            'rejection_reason' => null,
            'source' => Opportunity::SOURCE_ORGANIZATION,
        ]);

        return redirect()
            ->route('organization.opportunities.index')
            ->with('success', 'Opportunity submitted for admin review.');
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('deleteOrganization', $opportunity);

        $opportunity->delete();

        return redirect()
            ->route('organization.opportunities.index')
            ->with('success', 'Draft opportunity deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', HubOpportunityController::TYPES)],
            'description' => ['required', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date'],
            'application_url' => ['nullable', 'url', 'max:2048'],
            'eligibility' => ['nullable', 'string', 'max:5000'],
            'skill_ids' => ['required', 'array', 'min:1'],
            'skill_ids.*' => ['integer', 'exists:skills,id'],
            'approval_status' => ['prohibited'],
            'organization_id' => ['prohibited'],
        ]);

        if (! empty($validated['application_url'])) {
            $scheme = strtolower((string) parse_url($validated['application_url'], PHP_URL_SCHEME));
            if (! in_array($scheme, ['http', 'https'], true)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'application_url' => 'The application URL must start with http or https.',
                ]);
            }
        }

        return $validated;
    }

    private function intent(Request $request): string
    {
        return $request->input('intent') === 'submit' ? 'submit' : 'draft';
    }

    /**
     * @param  list<mixed>  $skillIds
     * @return list<int>
     */
    private function uniqueSkillIds(array $skillIds): array
    {
        return array_values(array_unique(array_map('intval', $skillIds)));
    }

    /**
     * @param  list<int>  $skillIds
     */
    private function skillNames(array $skillIds): string
    {
        return Skill::query()
            ->whereIn('id', $skillIds)
            ->orderBy('name')
            ->pluck('name')
            ->implode(', ');
    }

    private function organization(): Organization
    {
        $organization = request()->user()->currentOrganization();
        abort_unless($organization, 403);

        return $organization;
    }
}
