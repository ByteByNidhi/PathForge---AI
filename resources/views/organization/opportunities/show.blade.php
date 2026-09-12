@extends('organization.layout')

@section('title', $opportunity->title)

@section('content')
    <h2>{{ $opportunity->title }}</h2>
    <p>{{ $opportunity->approvalStatusLabel() }}</p>

    @if ($opportunity->isRejected() && $opportunity->rejection_reason)
        <div class="pf-flash pf-flash--error">Rejection reason: {{ $opportunity->rejection_reason }}</div>
    @endif

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('organization.opportunities.index') }}">Back</a>
        @include('organization.opportunities._actions', ['opportunity' => $opportunity, 'isOwner' => $isOwner])
    </div>

    <article class="pf-card" style="margin-top:20px;">
        <dl>
            <dt>Type</dt>
            <dd>{{ $opportunity->type }}</dd>
            <dt>Location</dt>
            <dd>{{ $opportunity->location ?: '—' }}</dd>
            <dt>Deadline</dt>
            <dd>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</dd>
            <dt>Application URL</dt>
            <dd>{{ $opportunity->application_url ?: '—' }}</dd>
            <dt>Description</dt>
            <dd>{{ $opportunity->description }}</dd>
            <dt>Eligibility</dt>
            <dd>{{ $opportunity->eligibility ?: '—' }}</dd>
            <dt>Required skills</dt>
            <dd>{{ $opportunity->skills->pluck('name')->implode(', ') ?: $opportunity->required_skills }}</dd>
        </dl>
    </article>
@endsection
