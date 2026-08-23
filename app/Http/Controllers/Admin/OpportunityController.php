<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\OpportunityController as HubOpportunityController;
use App\Models\Opportunity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpportunityController extends Controller
{
    public function index(): View
    {
        $opportunities = Opportunity::query()
            ->orderByDesc('id')
            ->get();

        return view('admin.opportunities.index', [
            'opportunities' => $opportunities,
        ]);
    }

    public function create(): View
    {
        return view('admin.opportunities.form', [
            'opportunity' => new Opportunity,
            'types' => HubOpportunityController::TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Opportunity::query()->create($this->validated($request));

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity created.');
    }

    public function edit(Opportunity $opportunity): View
    {
        return view('admin.opportunities.form', [
            'opportunity' => $opportunity,
            'types' => HubOpportunityController::TYPES,
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($this->validated($request));

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity updated.');
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->delete();

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', HubOpportunityController::TYPES)],
            'description' => ['nullable', 'string'],
            'required_skills' => ['nullable', 'string'],
            'eligibility' => ['nullable', 'string'],
            'deadline' => ['nullable', 'date'],
            'application_url' => ['required', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['deadline'] = $validated['deadline'] ?: null;

        return $validated;
    }
}
