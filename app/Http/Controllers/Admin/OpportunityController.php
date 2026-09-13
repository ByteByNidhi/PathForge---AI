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
            ->with('owningOrganization')
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
            'deadlineMin' => now()->toDateString(),
            'deadlineMax' => now()->addYears(2)->toDateString(),
            'lockExternalDeadline' => false,
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
        abort_unless($opportunity->isPending() || $opportunity->isRejected(), 403);

        $opportunity->update([
            'approval_status' => Opportunity::APPROVAL_APPROVED,
            'rejection_reason' => null,
        ]);

        return redirect()
            ->route('admin.opportunities.index')
            ->with('success', 'Opportunity approved.');
    }

    public function reject(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($opportunity->isPending() || $opportunity->isApproved(), 403);

        $validated = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $opportunity->update([
            'approval_status' => Opportunity::APPROVAL_REJECTED,
            'rejection_reason' => $validated['rejection_reason'] ?? null,
        ]);

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

        $isHimalayas = $opportunity->source === Opportunity::SOURCE_HIMALAYAS;

        return view('admin.opportunities.form', [
            'opportunity' => $opportunity,
            'types' => $types,
            'deadlineMin' => $isHimalayas ? null : now()->toDateString(),
            'deadlineMax' => $isHimalayas
                ? null
                : now()->addYears($opportunity->source === Opportunity::SOURCE_ORGANIZATION ? 1 : 2)->toDateString(),
            'lockExternalDeadline' => $isHimalayas,
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

        $isHimalayas = $opportunity?->source === Opportunity::SOURCE_HIMALAYAS;
        $deadlineRules = ['nullable', 'date'];
        $messages = [];

        if (! $isHimalayas) {
            $maxYears = $opportunity?->source === Opportunity::SOURCE_ORGANIZATION ? 1 : 2;
            $deadlineRules[] = 'after_or_equal:today';
            $deadlineRules[] = 'before_or_equal:'.now()->addYears($maxYears)->toDateString();
            $messages['deadline.after_or_equal'] = 'The deadline must be today or later.';
            $messages['deadline.before_or_equal'] = $maxYears === 1
                ? 'Organization opportunity deadlines cannot be more than 1 year from today.'
                : 'Manual opportunity deadlines cannot be more than 2 years from today.';
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', $types)],
            'description' => ['nullable', 'string'],
            'required_skills' => ['nullable', 'string'],
            'eligibility' => ['nullable', 'string'],
            'deadline' => $deadlineRules,
            'application_url' => ['required', 'url', 'max:2048'],
            'location' => ['nullable', 'string', 'max:255'],
        ], $messages);

        $validated['deadline'] = $validated['deadline'] ?: null;

        return $validated;
    }
}
