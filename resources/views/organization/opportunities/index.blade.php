@extends('organization.layout')

@section('title', 'My Opportunities')

@section('content')
    <h2>My Opportunities</h2>
    <p>Only listings belonging to {{ $organization->name }} are shown here.</p>

    @if ($isOwner)
        <div class="actions">
            <a class="btn" href="{{ route('organization.opportunities.create') }}">Create Opportunity</a>
        </div>
    @endif

    @if ($opportunities->isEmpty())
        <p class="muted">No opportunities yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunities as $opportunity)
                    <tr>
                        <td>{{ $opportunity->title }}</td>
                        <td>{{ $opportunity->type }}</td>
                        <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        <td>
                            {{ $opportunity->approvalStatusLabel() }}
                            @if ($opportunity->isRejected() && $opportunity->rejection_reason)
                                <div class="muted">{{ $opportunity->rejection_reason }}</div>
                            @endif
                        </td>
                        <td>{{ $opportunity->created_at?->toDateString() }}</td>
                        <td>
                            @include('organization.opportunities._actions', ['opportunity' => $opportunity, 'isOwner' => $isOwner])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
