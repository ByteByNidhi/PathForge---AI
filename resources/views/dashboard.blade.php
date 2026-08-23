@extends('layouts.app')

@section('title', 'Dashboard')
@section('kicker', 'Overview')
@section('heading', 'Dashboard')
@section('lede', 'Your career state, next milestone, and the work that moves you forward.')

@section('content')
    <div class="pf-dash">
        <section class="pf-card pf-card--hero pf-card--glow pf-span-2">
            <div>
                <p class="pf-kicker">Career state</p>
                <h2>{{ $user->name }}</h2>
                <p class="muted">{{ $user->email }}</p>
                <p>{{ $path?->path_name ?? 'No roadmap selected yet.' }}</p>
                <div class="pf-stat-row" style="margin-top:18px;">
                    <div class="pf-stat"><span>Level</span><strong>{{ $user->level ?? 1 }}</strong></div>
                    <div class="pf-stat"><span>XP</span><strong>{{ $user->xp ?? 0 }}</strong></div>
                    <div class="pf-stat"><span>Skills</span><strong>{{ $skills->count() }}</strong></div>
                    <div class="pf-stat"><span>Roadmap</span><strong>{{ $progressPercent }}%</strong></div>
                </div>
            </div>
            <div>
                <p class="muted">Progress to next level</p>
                <div class="pf-progress" style="margin:10px 0 8px;"><span style="width: {{ $xpIntoLevel }}%;"></span></div>
                <p class="muted">{{ $xpIntoLevel }} / 100 XP in this level</p>
                <div class="actions">
                    <a class="btn" href="{{ url('/ai-studio') }}">Open AI Studio</a>
                    <a class="btn btn-secondary" href="{{ route('profile') }}">Profile</a>
                </div>
            </div>
        </section>

        <section class="pf-card">
            <h2>Current quest</h2>
            @if ($currentStep && $path)
                <p class="muted">{{ $path->path_name }}</p>
                <h3 style="font-family:var(--pf-serif);font-weight:400;font-size:1.6rem;margin:8px 0;">{{ $currentStep->title }}</h3>
                <p class="muted">Step {{ $currentStep->step_no }} · {{ $currentStep->xp_reward }} XP</p>
                <form method="POST" action="{{ route('roadmaps.complete', [$path, $currentStep]) }}">
                    @csrf
                    <button class="btn" type="submit">Complete this step</button>
                </form>
            @elseif ($path)
                <p>You have completed every step on this roadmap.</p>
                <a class="btn" href="{{ route('roadmaps.show', $path) }}">Continue Roadmap</a>
            @else
                <p class="muted">Choose a roadmap to unlock your next quest.</p>
                <a class="btn" href="{{ route('roadmaps.index') }}">Choose a roadmap</a>
            @endif
        </section>

        <section class="pf-card">
            <h2>Roadmap progress</h2>
            @if ($path)
                <p>{{ $path->path_name }}</p>
                <p class="muted">{{ $completedSteps }} / {{ $totalSteps }} steps complete</p>
                <div class="progress-wrap">
                    <div class="progress-bar" style="width: {{ $progressPercent }}%;"></div>
                </div>
                <a class="btn" href="{{ route('roadmaps.show', $path) }}">Continue Roadmap</a>
            @else
                <p class="muted">No learning path selected yet.</p>
                <a class="btn" href="{{ route('roadmaps.index') }}">Browse roadmaps</a>
            @endif
        </section>

        <section class="pf-card">
            <h2>Skills</h2>
            @if ($skills->isEmpty())
                <p class="muted">Add skills on your profile to improve opportunity matching.</p>
            @else
                <div class="chips">
                    @foreach ($skills as $skill)
                        <span class="chip">{{ $skill->name }}</span>
                    @endforeach
                </div>
            @endif
            <a class="btn btn-secondary" href="{{ route('profile') }}">Manage skills</a>
        </section>

        <section class="pf-card">
            <h2>Achievements</h2>
            <div class="badge-grid">
                @foreach ($achievements as $item)
                    @include('achievements.badge', ['item' => $item])
                @endforeach
            </div>
            <div class="actions">
                <a class="btn btn-secondary" href="{{ route('achievements.index') }}">All achievements</a>
            </div>
        </section>

        <section class="pf-card pf-span-2">
            <h2>Recommended opportunities</h2>
            @forelse ($recommendedOpportunities as $opportunity)
                <article class="item" style="margin-bottom:12px;">
                    <h3 style="margin:0 0 6px;">{{ $opportunity->title }}</h3>
                    <p class="muted">{{ $opportunity->organization }} · {{ $opportunity->type }}</p>
                    <p>
                        @if ($opportunity->skill_match['percent'] !== null)
                            Skill match: {{ $opportunity->skill_match['percent'] }}%
                        @else
                            Add your skills to calculate your match
                        @endif
                    </p>
                    <a class="btn" href="{{ route('opportunities.show', $opportunity) }}">View Details</a>
                </article>
            @empty
                <p class="muted">No opportunities available yet.</p>
            @endforelse
            <a class="btn btn-secondary" href="{{ route('opportunities.index') }}">Opportunity Hub</a>
        </section>

        <section class="pf-card pf-span-2">
            <h2>Recent activity</h2>
            @forelse ($recentCompletions as $row)
                <p>{{ $row->roadmapStep->title ?? 'Roadmap step' }} · completed {{ $row->completed_at?->diffForHumans() }}</p>
            @empty
                <p class="muted">Complete a roadmap step to start your activity log.</p>
            @endforelse
        </section>
    </div>
@endsection
