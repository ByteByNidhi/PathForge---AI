@extends('organization.layout')

@section('title', 'Organization Dashboard')

@section('content')
    <h2>{{ $organization->name }}</h2>
    <p>Track drafts, review status, and opportunities your organization has submitted.</p>

    <div class="pf-stat-row" style="margin: 20px 0;">
        <div class="pf-stat"><span>Total</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="pf-stat"><span>Draft</span><strong>{{ $stats['draft'] }}</strong></div>
        <div class="pf-stat"><span>Pending review</span><strong>{{ $stats['pending'] }}</strong></div>
        <div class="pf-stat"><span>Approved</span><strong>{{ $stats['approved'] }}</strong></div>
        <div class="pf-stat"><span>Rejected</span><strong>{{ $stats['rejected'] }}</strong></div>
    </div>

    @if ($isOwner)
        <div class="actions">
            <a class="btn" href="{{ route('organization.opportunities.create') }}">Create Opportunity</a>
        </div>
    @endif

    <h3>Recent opportunities</h3>
    @if ($recent->isEmpty())
        <p class="muted">No opportunities yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recent as $opportunity)
                    <tr>
                        <td>{{ $opportunity->title }}</td>
                        <td>{{ $opportunity->type }}</td>
                        <td>
                            {{ $opportunity->approvalStatusLabel() }}
                            @if ($opportunity->isRejected() && $opportunity->rejection_reason)
                                <div class="muted">{{ $opportunity->rejection_reason }}</div>
                            @endif
                        </td>
                        <td>{{ $opportunity->created_at?->toDateString() }}</td>
                        <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        <td>
                            @include('organization.opportunities._actions', ['opportunity' => $opportunity, 'isOwner' => $isOwner])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
