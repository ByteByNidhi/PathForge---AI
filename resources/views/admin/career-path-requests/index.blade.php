@extends('admin.layout')

@section('title', 'Career Path Requests')

@section('content')
    <h2>Career Path Requests</h2>
    <p>Unsupported career interests collected during onboarding. These are notes for future PathForge paths — no AI roadmap is generated from them.</p>

    @if ($groups->isEmpty())
        <p class="muted">No career path requests yet.</p>
    @else
        <div class="cards" style="margin-bottom:24px;">
            @foreach ($groups as $group)
                <div class="card">
                    <div class="label">{{ $group->requested_path }}</div>
                    <div class="value">{{ $group->request_count }}</div>
                    <p class="muted" style="margin-top:8px;">
                        Requested by: {{ $group->request_count }} {{ $group->request_count === 1 ? 'user' : 'users' }}
                    </p>
                    <p class="muted">
                        Status: {{ $group->pending_count > 0 ? 'Pending' : 'Reviewed' }}
                    </p>
                    @if ($group->pending_count > 0)
                        <form method="POST" action="{{ route('admin.career-path-requests.review') }}" style="margin-top:12px;">
                            @csrf
                            <input type="hidden" name="requested_path" value="{{ $group->requested_path }}">
                            <button class="btn btn-secondary" type="submit">Mark as reviewed</button>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="panel">
            <h2>All requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Career path</th>
                        <th>Requested by</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $request)
                        <tr>
                            <td>{{ $request->requested_path }}</td>
                            <td>{{ $request->user->name ?? 'Unknown' }}</td>
                            <td>{{ $request->created_at?->format('M j, Y') }}</td>
                            <td>{{ ucfirst($request->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
