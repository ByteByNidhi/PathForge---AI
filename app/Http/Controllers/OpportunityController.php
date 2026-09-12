<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Throwable;

class OpportunityController extends Controller
{
    public const TYPES = [
        'Hackathon',
        'Internship',
        'Scholarship',
        'Research',
    ];

    public const STATUSES = [
        'open',
        'closing_soon',
        'closed',
    ];

    public const SORTS = [
        'match',
        'nearest',
        'latest',
    ];

    public const DEFAULT_SORT = 'match';

    public function index(Request $request): View
    {
        $selectedType = $request->query('type');
        $selectedLocation = trim((string) $request->query('location', ''));
        $selectedStatus = $request->query('status');
        $selectedSkill = trim((string) $request->query('skill', ''));
        $sort = $request->query('sort', self::DEFAULT_SORT);
        $search = trim((string) $request->query('q', ''));

        $selectedType = in_array($selectedType, self::TYPES, true) ? $selectedType : null;
        $selectedStatus = in_array($selectedStatus, self::STATUSES, true) ? $selectedStatus : null;
        $sort = in_array($sort, self::SORTS, true) ? $sort : self::DEFAULT_SORT;

        $error = null;
        $opportunities = collect();
        $locations = collect();
        $skillOptions = collect();
        $totalCount = 0;
        $userSkillNames = [];
        $savedIds = [];

        try {
            $user = $request->user();
            $userSkillNames = $user
                ->skills()
                ->pluck('name')
                ->all();

            $savedIds = $user->savedOpportunities()
                ->pluck('opportunities.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $totalCount = Opportunity::query()->visibleToStudents()->count();
            $locations = Opportunity::query()
                ->visibleToStudents()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location');

            $skillOptions = $this->skillOptions();

            $query = Opportunity::query()
                ->visibleToStudents()
                ->with('skills');

            if ($selectedType !== null) {
                $query->where('type', $selectedType);
            }

            if ($selectedLocation !== '') {
                $query->where('location', $selectedLocation);
            }

            if ($selectedSkill !== '') {
                $escaped = $this->escapeLike($selectedSkill);
                $query->where(function ($builder) use ($escaped, $selectedSkill) {
                    $builder->where('required_skills', 'like', '%'.$escaped.'%')
                        ->orWhereHas('skills', function ($skills) use ($selectedSkill) {
                            $skills->whereRaw('LOWER(name) = ?', [mb_strtolower($selectedSkill)]);
                        });
                });
            }

            if ($search !== '') {
                $term = '%'.$this->escapeLike($search).'%';
                $query->where(function ($builder) use ($term) {
                    $builder->where('title', 'like', $term)
                        ->orWhere('organization', 'like', $term)
                        ->orWhere('type', 'like', $term)
                        ->orWhere('required_skills', 'like', $term);
                });
            }

            $today = now()->startOfDay();
            $soon = $today->copy()->addDays(Opportunity::CLOSING_SOON_DAYS);

            if ($selectedStatus === Opportunity::STATUS_CLOSED) {
                $query->whereNotNull('deadline')->whereDate('deadline', '<', $today->toDateString());
            } elseif ($selectedStatus === Opportunity::STATUS_CLOSING_SOON) {
                $query->whereNotNull('deadline')
                    ->whereDate('deadline', '>=', $today->toDateString())
                    ->whereDate('deadline', '<=', $soon->toDateString());
            } elseif ($selectedStatus === Opportunity::STATUS_OPEN) {
                $query->where(function ($builder) use ($soon) {
                    $builder->whereNull('deadline')
                        ->orWhereDate('deadline', '>', $soon->toDateString());
                });
            }

            $opportunities = $query->get()->map(function (Opportunity $opportunity) use ($userSkillNames) {
                $opportunity->setAttribute('skill_match', $opportunity->skillMatch($userSkillNames));
                $opportunity->setAttribute('deadline_status', $opportunity->deadlineStatus());
                $opportunity->setAttribute('deadline_status_label', $opportunity->deadlineStatusLabel());

                return $opportunity;
            });

            $opportunities = $this->sortOpportunities($opportunities, $sort);
        } catch (Throwable $exception) {
            report($exception);
            $error = 'Opportunities could not be loaded. Please try again.';
        }

        $hasFilters = $selectedType !== null
            || $selectedLocation !== ''
            || $selectedStatus !== null
            || $selectedSkill !== ''
            || $search !== '';

        return view('opportunities.index', [
            'opportunities' => $opportunities,
            'types' => self::TYPES,
            'selectedType' => $selectedType,
            'selectedLocation' => $selectedLocation,
            'selectedStatus' => $selectedStatus,
            'selectedSkill' => $selectedSkill,
            'sort' => $sort,
            'search' => $search,
            'locations' => $locations,
            'skillOptions' => $skillOptions,
            'hasUserSkills' => $userSkillNames !== [],
            'savedIds' => $savedIds,
            'totalCount' => $totalCount,
            'hasFilters' => $hasFilters,
            'error' => $error,
            'queryBase' => array_filter([
                'q' => $search !== '' ? $search : null,
                'type' => $selectedType,
                'location' => $selectedLocation !== '' ? $selectedLocation : null,
                'status' => $selectedStatus,
                'skill' => $selectedSkill !== '' ? $selectedSkill : null,
                'sort' => $sort !== self::DEFAULT_SORT ? $sort : null,
            ]),
        ]);
    }

    public function saved(Request $request): View
    {
        $userSkillNames = $request->user()
            ->skills()
            ->pluck('name')
            ->all();

        $opportunities = $request->user()
            ->savedOpportunities()
            ->visibleToStudents()
            ->with('skills')
            ->orderByDesc('saved_opportunities.saved_at')
            ->orderByDesc('saved_opportunities.id')
            ->get()
            ->map(function (Opportunity $opportunity) use ($userSkillNames) {
                $opportunity->setAttribute('skill_match', $opportunity->skillMatch($userSkillNames));
                $opportunity->setAttribute('deadline_status', $opportunity->deadlineStatus());
                $opportunity->setAttribute('deadline_status_label', $opportunity->deadlineStatusLabel());

                return $opportunity;
            });

        return view('opportunities.saved', [
            'opportunities' => $opportunities,
        ]);
    }

    public function show(Opportunity $opportunity): View
    {
        abort_unless($opportunity->isVisibleToStudents(), 404);

        $opportunity->loadMissing('skills');

        $user = request()->user();
        $userSkillNames = $user
            ->skills()
            ->pluck('name')
            ->all();

        $isSaved = $user->savedOpportunities()
            ->where('opportunities.id', $opportunity->id)
            ->exists();

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'skillMatch' => $opportunity->skillMatch($userSkillNames),
            'deadlineStatus' => $opportunity->deadlineStatus(),
            'deadlineStatusLabel' => $opportunity->deadlineStatusLabel(),
            'isSaved' => $isSaved,
        ]);
    }

    public function save(Request $request, Opportunity $opportunity): RedirectResponse
    {
        abort_unless($opportunity->isVisibleToStudents(), 404);
        $this->authorize('save', $opportunity);

        $request->user()->savedOpportunities()->syncWithoutDetaching([
            $opportunity->id => ['saved_at' => now()],
        ]);

        return back()->with('success', 'Opportunity saved.');
    }

    public function unsave(Request $request, Opportunity $opportunity): RedirectResponse
    {
        $this->authorize('unsave', $opportunity);

        $request->user()->savedOpportunities()->detach($opportunity->id);

        return back()->with('success', 'Opportunity removed from saved.');
    }

    /**
     * @param  Collection<int, Opportunity>  $opportunities
     * @return Collection<int, Opportunity>
     */
    private function sortOpportunities(Collection $opportunities, string $sort): Collection
    {
        if ($sort === 'latest') {
            return $opportunities->sortByDesc(function (Opportunity $opportunity) {
                return $opportunity->deadline?->timestamp ?? 0;
            })->values();
        }

        if ($sort === 'nearest') {
            return $opportunities->sortBy(function (Opportunity $opportunity) {
                $closed = $opportunity->getAttribute('deadline_status') === Opportunity::STATUS_CLOSED;
                $timestamp = $opportunity->deadline?->timestamp ?? PHP_INT_MAX;

                return [$closed ? 1 : 0, $timestamp];
            })->values();
        }

        return $opportunities->sortBy(function (Opportunity $opportunity) {
            $match = $opportunity->getAttribute('skill_match');
            $percent = ($match['has_user_skills'] ?? false) ? ($match['percent'] ?? -1) : -1;

            return [
                -$percent,
                -($opportunity->created_at?->timestamp ?? 0),
                $opportunity->deadline?->timestamp ?? PHP_INT_MAX,
            ];
        })->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function skillOptions(): Collection
    {
        $fromText = Opportunity::query()
            ->visibleToStudents()
            ->whereNotNull('required_skills')
            ->pluck('required_skills')
            ->flatMap(fn (?string $raw) => Opportunity::parseSkillList($raw));

        $fromPivot = Skill::query()
            ->whereHas('opportunities', function ($query) {
                $query->visibleToStudents();
            })
            ->pluck('name');

        return $fromText
            ->merge($fromPivot)
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
