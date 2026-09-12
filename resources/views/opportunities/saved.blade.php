@extends('layouts.app')

@section('title', 'Saved opportunities')
@section('kicker', 'Market')
@section('heading', 'Saved opportunities')
@section('lede', 'Opportunities you have saved from the Opportunity Hub.')

@section('content')
    <p class="muted"><a href="{{ route('opportunities.index') }}">Back to Opportunity Hub</a></p>

    @forelse ($opportunities as $opportunity)
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
                @if ($opportunity->isHimalayasSourced())
                    · Source: <a href="https://himalayas.app" target="_blank" rel="noopener noreferrer">Himalayas</a>
                @endif
            </p>
            <p class="match">
                @if (! $match['has_user_skills'])
                    <a href="{{ route('profile') }}">Add your skills to calculate your match</a>
                @elseif ($match['percent'] === null)
                    Skill match is not available for this opportunity.
                @else
                    {{ $match['percent'] }}% Skill Match
                    @if (count($match['matched']))
                        · {{ collect($match['matched'])->map(fn ($skill) => '✓ '.$skill)->implode(' · ') }}
                    @else
                        · No matching skills yet
                    @endif
                @endif
            </p>
            <div class="actions">
                <a class="btn" href="{{ route('opportunities.show', $opportunity) }}">View Details</a>
                @include('opportunities._save', [
                    'opportunity' => $opportunity,
                    'isSaved' => true,
                ])
            </div>
        </article>
    @empty
        <p class="state">You have not saved any opportunities yet.</p>
    @endforelse
@endsection
