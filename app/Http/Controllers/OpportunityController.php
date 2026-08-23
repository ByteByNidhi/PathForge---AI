<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
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
        'nearest',
        'latest',
        'match',
    ];

    public function index(Request $request): View
    {
        $selectedType = $request->query('type');
        $selectedLocation = trim((string) $request->query('location', ''));
        $selectedStatus = $request->query('status');
        $selectedSkill = trim((string) $request->query('skill', ''));
        $sort = $request->query('sort', 'nearest');
        $search = trim((string) $request->query('q', ''));

        $selectedType = in_array($selectedType, self::TYPES, true) ? $selectedType : null;
        $selectedStatus = in_array($selectedStatus, self::STATUSES, true) ? $selectedStatus : null;
        $sort = in_array($sort, self::SORTS, true) ? $sort : 'nearest';

        $error = null;
        $opportunities = collect();
        $locations = collect();
        $skillOptions = collect();
        $totalCount = 0;
        $userSkillNames = [];

        try {
            $userSkillNames = $request->user()
                ->skills()
                ->pluck('name')
                ->all();

            $totalCount = Opportunity::query()->count();
            $locations = Opportunity::query()
                ->whereNotNull('location')
                ->where('location', '!=', '')
                ->distinct()
                ->orderBy('location')
                ->pluck('location');

            $skillOptions = $this->skillOptions();

            $query = Opportunity::query();

            if ($selectedType !== null) {
                $query->where('type', $selectedType);
            }

            if ($selectedLocation !== '') {
                $query->where('location', $selectedLocation);
            }

            if ($selectedSkill !== '') {
                $query->where('required_skills', 'like', '%'.$this->escapeLike($selectedSkill).'%');
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
            'totalCount' => $totalCount,
            'hasFilters' => $hasFilters,
            'error' => $error,
            'queryBase' => array_filter([
                'q' => $search !== '' ? $search : null,
                'type' => $selectedType,
                'location' => $selectedLocation !== '' ? $selectedLocation : null,
                'status' => $selectedStatus,
                'skill' => $selectedSkill !== '' ? $selectedSkill : null,
                'sort' => $sort !== 'nearest' ? $sort : null,
            ]),
        ]);
    }

    public function show(Opportunity $opportunity): View
    {
        $userSkillNames = request()->user()
            ->skills()
            ->pluck('name')
            ->all();

        return view('opportunities.show', [
            'opportunity' => $opportunity,
            'skillMatch' => $opportunity->skillMatch($userSkillNames),
            'deadlineStatus' => $opportunity->deadlineStatus(),
            'deadlineStatusLabel' => $opportunity->deadlineStatusLabel(),
        ]);
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

        if ($sort === 'match') {
            return $opportunities->sortByDesc(function (Opportunity $opportunity) {
                $match = $opportunity->getAttribute('skill_match');

                return $match['has_user_skills'] ? ($match['percent'] ?? -1) : -1;
            })->values();
        }

        return $opportunities->sortBy(function (Opportunity $opportunity) {
            $closed = $opportunity->getAttribute('deadline_status') === Opportunity::STATUS_CLOSED;
            $timestamp = $opportunity->deadline?->timestamp ?? PHP_INT_MAX;

            return [$closed ? 1 : 0, $timestamp];
        })->values();
    }

    /**
     * @return Collection<int, string>
     */
    private function skillOptions(): Collection
    {
        return Opportunity::query()
            ->whereNotNull('required_skills')
            ->pluck('required_skills')
            ->flatMap(fn (?string $raw) => Opportunity::parseSkillList($raw))
            ->unique()
            ->sort()
            ->values();
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
