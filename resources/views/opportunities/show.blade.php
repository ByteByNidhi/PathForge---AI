@extends('layouts.app')

@section('title', $opportunity->title)
@section('kicker', $opportunity->type)
@section('heading', $opportunity->title)

@section('content')
    @php
        $badgeClass = $deadlineStatus === 'closed' ? 'badge-closed' : ($deadlineStatus === 'closing_soon' ? 'badge-closing' : 'badge-open');
    @endphp

    <article class="pf-card">
        <p><span class="badge {{ $badgeClass }}">{{ $deadlineStatusLabel }}</span></p>
        <dl>
            <dt>Organization</dt>
            <dd>{{ $opportunity->organization }}</dd>
            <dt>Type</dt>
            <dd>{{ $opportunity->type }}</dd>
            <dt>Description</dt>
            <dd>{{ $opportunity->description }}</dd>
            <dt>Eligibility</dt>
            <dd>{{ $opportunity->eligibility }}</dd>
            <dt>Required skills</dt>
            <dd>{{ $opportunity->required_skills }}</dd>
            <dt>Skill match</dt>
            <dd>
                @if (! $skillMatch['has_user_skills'])
                    <a href="{{ route('profile') }}">Add your skills to calculate your match</a>
                @elseif ($skillMatch['percent'] === null)
                    Skill match is not available for this opportunity.
                @else
                    {{ $skillMatch['percent'] }}% Skill Match
                @endif
            </dd>
            @if ($skillMatch['has_user_skills'])
                <dt>Matched skills</dt>
                <dd>
                    @if (count($skillMatch['matched']))
                        {{ collect($skillMatch['matched'])->map(fn ($skill) => '✓ '.$skill)->implode(', ') }}
                    @else
                        No matching skills yet
                    @endif
                </dd>
                <dt>Missing skills</dt>
                <dd>{{ count($skillMatch['missing']) ? implode(', ', $skillMatch['missing']) : 'None' }}</dd>
            @endif
            <dt>Deadline</dt>
            <dd>{{ $opportunity->deadline ? $opportunity->deadline->format('M j, Y') : 'Not specified' }}</dd>
            <dt>Location</dt>
            <dd>{{ $opportunity->location }}</dd>
            @if ($opportunity->isHimalayasSourced())
                <dt>Source</dt>
                <dd><a href="https://himalayas.app" target="_blank" rel="noopener noreferrer">Himalayas</a></dd>
            @endif
        </dl>
        <div class="actions">
            @if ($opportunity->hasValidApplicationUrl())
                <a class="btn" href="{{ $opportunity->application_url }}" target="_blank" rel="noopener noreferrer">Apply</a>
            @else
                <p class="muted">No application link is available yet.</p>
            @endif
            @include('opportunities._save', [
                'opportunity' => $opportunity,
                'isSaved' => $isSaved,
            ])
        </div>
    </article>
@endsection
