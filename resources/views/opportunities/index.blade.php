@extends('layouts.app')

@section('title', 'Opportunity Hub')
@section('kicker', 'Market')
@section('heading', 'Opportunity Hub')
@section('lede', 'Discover hackathons, internships, scholarships, and research opportunities.')

@section('content')
    <div class="filters">
        <a class="{{ $selectedType === null ? 'active' : '' }}" href="{{ route('opportunities.index', array_filter(array_merge($queryBase, ['type' => null]))) }}">All</a>
        @foreach ($types as $type)
            <a class="{{ $selectedType === $type ? 'active' : '' }}" href="{{ route('opportunities.index', array_filter(array_merge($queryBase, ['type' => $type]))) }}">
                @if ($type === 'Hackathon')
                    Hackathons
                @elseif ($type === 'Internship')
                    Internships
                @elseif ($type === 'Scholarship')
                    Scholarships
                @else
                    Research
                @endif
            </a>
        @endforeach
    </div>

    <form id="hub-filters" method="GET" action="{{ route('opportunities.index') }}">
        @if ($selectedType)
            <input type="hidden" name="type" value="{{ $selectedType }}">
        @endif
        <input type="text" name="q" value="{{ $search }}" placeholder="Search title, organization, type, or skills">
        <select name="location" aria-label="Filter by location">
            <option value="">All locations</option>
            @foreach ($locations as $location)
                <option value="{{ $location }}" @selected($selectedLocation === $location)>{{ $location }}</option>
            @endforeach
        </select>
        <select name="status" aria-label="Filter by deadline status">
            <option value="">All deadlines</option>
            <option value="open" @selected($selectedStatus === 'open')>Open</option>
            <option value="closing_soon" @selected($selectedStatus === 'closing_soon')>Closing Soon</option>
            <option value="closed" @selected($selectedStatus === 'closed')>Closed</option>
        </select>
        <select name="skill" aria-label="Filter by skill">
            <option value="">All skills</option>
            @foreach ($skillOptions as $skill)
                <option value="{{ $skill }}" @selected($selectedSkill === $skill)>{{ $skill }}</option>
            @endforeach
        </select>
        <select name="sort" aria-label="Sort opportunities">
            <option value="nearest" @selected($sort === 'nearest')>Nearest deadline</option>
            <option value="latest" @selected($sort === 'latest')>Latest deadline</option>
            <option value="match" @selected($sort === 'match')>Highest skill match</option>
        </select>
        <button class="btn" type="submit">Apply</button>
    </form>

    <p id="hub-loading" class="loading">Loading opportunities…</p>

    @if ($error)
        <p class="state state-error">{{ $error }}</p>
    @elseif ($totalCount === 0)
        <p class="state">No opportunities are available yet.</p>
    @elseif ($opportunities->isEmpty())
        <p class="state">No opportunities match your filters.</p>
    @else
        @foreach ($opportunities as $opportunity)
            @php
                $match = $opportunity->skill_match;
                $status = $opportunity->deadline_status;
                $badgeClass = $status === 'closed' ? 'badge-closed' : ($status === 'closing_soon' ? 'badge-closing' : 'badge-open');
            @endphp
            <article class="pf-card item" style="margin-bottom:12px;">
                <h2>
                    {{ $opportunity->title }}
                    <span class="badge {{ $badgeClass }}">{{ $opportunity->deadline_status_label }}</span>
                </h2>
                <p class="meta muted">
                    {{ $opportunity->type }}
                    · {{ $opportunity->organization }}
                    · {{ $opportunity->location }}
                    · Deadline: {{ $opportunity->deadline ? $opportunity->deadline->format('M j, Y') : 'Not specified' }}
                </p>
                <p class="match">
                    @if (! $match['has_user_skills'])
                        <a href="{{ route('profile') }}">Add your skills to calculate your match</a>
                    @elseif ($match['percent'] === null)
                        Skill match is not available for this opportunity.
                    @else
                        Skill match: {{ $match['percent'] }}%
                        @if (count($match['matched']))
                            · Matched: {{ implode(', ', $match['matched']) }}
                        @endif
                        @if (count($match['missing']))
                            · Missing: {{ implode(', ', $match['missing']) }}
                        @endif
                    @endif
                </p>
                <a class="btn" href="{{ route('opportunities.show', $opportunity) }}">View Details</a>
            </article>
        @endforeach
    @endif
@endsection

@section('scripts')
    <script>
        document.getElementById('hub-filters')?.addEventListener('submit', function () {
            var loading = document.getElementById('hub-loading');
            if (loading) {
                loading.style.display = 'block';
            }
        });
    </script>
@endsection
