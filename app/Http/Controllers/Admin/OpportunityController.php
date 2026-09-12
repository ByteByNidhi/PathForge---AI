<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\HimalayasServiceException;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OpportunityController as HubOpportunityController;
use App\Models\LearningPath;
use App\Models\Opportunity;
use App\Services\OpportunityImportService;
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

        $pending = $opportunities
            ->where('approval_status', Opportunity::APPROVAL_PENDING)
            ->values();

        return view('admin.opportunities.index', [
            'opportunities' => $opportunities,
            'pending' => $pending,
            'learningPaths' => LearningPath::query()->orderBy('path_name')->get(),
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
        $data = $this->validated($request);
        $data['approval_status'] = Opportunity::APPROVAL_APPROVED;

        Opportunity::query()->create($data);

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity created.');
    }

    public function fetch(Request $request, OpportunityImportService $importer): RedirectResponse
    {
        $validated = $request->validate([
            'learning_path_id' => ['nullable', 'integer', 'exists:learning_paths,id'],
            'q' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $path = null;

        if (! empty($validated['learning_path_id'])) {
            $path = LearningPath::query()->find($validated['learning_path_id']);
            if ($path && $query === '') {
                $query = $importer->searchQueryForPath($path);
            }
        }

        if ($query === '') {
            return redirect()
                ->route('admin.opportunities.index')
                ->with('error', 'Choose a learning path or enter a search query before fetching.');
        }

        $filters = array_filter([
            'country' => $validated['country'] ?? null,
            'limit' => (int) config('services.himalayas.max_results', 10),
        ], static fn ($value) => $value !== null && $value !== '');

        try {
            $result = $importer->importFromSearch($query, $filters);
        } catch (HimalayasServiceException $exception) {
            return redirect()
                ->route('admin.opportunities.index')
                ->with('error', $exception->getMessage());
        }

        if ($result['fetched'] === 0) {
            return redirect()
                ->route('admin.opportunities.index')
                ->with('error', 'No jobs matched that search. Try another learning path or query.');
        }

        $pathLabel = $path?->path_name ? ' for '.$path->path_name : '';

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', sprintf(
                'Fetched %d job%s%s. Newly imported: %d. Duplicates skipped: %d. New listings stay pending until approved.',
                $result['fetched'],
                $result['fetched'] === 1 ? '' : 's',
                $pathLabel,
                $result['imported'],
                $result['duplicates']
            ));
    }

    public function approve(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update(['approval_status' => Opportunity::APPROVAL_APPROVED]);

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity approved.');
    }

    public function reject(Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update(['approval_status' => Opportunity::APPROVAL_REJECTED]);

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity rejected.');
    }

    public function edit(Opportunity $opportunity): View
    {
        $types = HubOpportunityController::TYPES;
        if ($opportunity->type && ! in_array($opportunity->type, $types, true)) {
            $types[] = $opportunity->type;
        }

        return view('admin.opportunities.form', [
            'opportunity' => $opportunity,
            'types' => $types,
        ]);
    }

    public function update(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $opportunity->update($this->validated($request, $opportunity));

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
    private function validated(Request $request, ?Opportunity $opportunity = null): array
    {
        $types = HubOpportunityController::TYPES;
        if ($opportunity?->type && ! in_array($opportunity->type, $types, true)) {
            $types[] = $opportunity->type;
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', $types)],
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
