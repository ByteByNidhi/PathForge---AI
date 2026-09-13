@extends('admin.layout')

@section('title', 'Opportunities')

@section('content')
    <h2>Opportunities</h2>
    <p>Manage listings shown in the Opportunity Hub.</p>

    <div class="actions">
        <a class="btn" href="{{ route('admin.opportunities.create') }}">Add opportunity</a>
    </div>

    <section class="pf-card" style="margin: 20px 0;">
        <h3 style="margin-top:0;">Opportunity Intelligence</h3>
        <p class="muted">Fetch a small batch of remote jobs from Himalayas. New jobs are stored as pending and are hidden from users until you approve them.</p>

        <form method="POST" action="{{ route('admin.opportunities.fetch') }}">
            @csrf
            <div class="field">
                <label for="learning_path_id">Learning path</label>
                <select id="learning_path_id" name="learning_path_id">
                    <option value="">Select a path (optional)</option>
                    @foreach ($learningPaths as $path)
                        <option value="{{ $path->id }}" @selected((string) old('learning_path_id') === (string) $path->id)>{{ $path->path_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="q">Search query</label>
                <input id="q" name="q" value="{{ old('q') }}" placeholder="Leave blank to use the selected path name and skills">
                @error('q') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="country">Country (optional)</label>
                <input id="country" name="country" value="{{ old('country') }}" placeholder="e.g. India">
            </div>
            <button class="btn" type="submit">Fetch New Opportunities</button>
        </form>
    </section>

    @if ($pending->isNotEmpty())
        <h3>Pending review</h3>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($pending as $opportunity)
                    <tr>
                        <td>{{ $opportunity->title }}</td>
                        <td>{{ $opportunity->owningOrganization?->name ?? $opportunity->organization }}</td>
                        <td>{{ $opportunity->sourceLabel() }}</td>
                        <td>{{ $opportunity->approvalStatusLabel() }}</td>
                        <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        <td>
                            <form class="inline-form" method="POST" action="{{ route('admin.opportunities.approve', $opportunity) }}">
                                @csrf
                                <button class="btn" type="submit">Approve</button>
                            </form>
                            <form class="inline-form" method="POST" action="{{ route('admin.opportunities.reject', $opportunity) }}">
                                @csrf
                                <input name="rejection_reason" placeholder="Rejection reason (optional)" style="max-width:180px;">
                                <button class="btn btn-danger" type="submit">Reject</button>
                            </form>
                            <a href="{{ route('admin.opportunities.edit', $opportunity) }}">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if ($opportunities->isEmpty())
        <p class="muted">No opportunities yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Type</th>
                    <th>Source</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunities as $opportunity)
                    <tr>
                        <td>{{ $opportunity->title }}</td>
                        <td>{{ $opportunity->owningOrganization?->name ?? $opportunity->organization }}</td>
                        <td>{{ $opportunity->type }}</td>
                        <td>{{ $opportunity->sourceLabel() }}</td>
                        <td>{{ $opportunity->approvalStatusLabel() }}</td>
                        <td>{{ $opportunity->location ?? '—' }}</td>
                        <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        <td>
                            @if ($opportunity->isPending())
                                <form class="inline-form" method="POST" action="{{ route('admin.opportunities.approve', $opportunity) }}">
                                    @csrf
                                    <button class="btn" type="submit">Approve</button>
                                </form>
                                <form class="inline-form" method="POST" action="{{ route('admin.opportunities.reject', $opportunity) }}">
                                    @csrf
                                    <input name="rejection_reason" placeholder="Rejection reason (optional)" style="max-width:180px;">
                                    <button class="btn btn-danger" type="submit">Reject</button>
                                </form>
                            @elseif ($opportunity->isApproved() && $opportunity->source)
                                <form class="inline-form" method="POST" action="{{ route('admin.opportunities.reject', $opportunity) }}">
                                    @csrf
                                    <input name="rejection_reason" placeholder="Rejection reason (optional)" style="max-width:180px;">
                                    <button class="btn btn-danger" type="submit">Reject</button>
                                </form>
                            @elseif ($opportunity->isRejected())
                                <form class="inline-form" method="POST" action="{{ route('admin.opportunities.approve', $opportunity) }}">
                                    @csrf
                                    <button class="btn" type="submit">Approve</button>
                                </form>
                            @endif
                            <a href="{{ route('admin.opportunities.edit', $opportunity) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('admin.opportunities.destroy', $opportunity) }}" data-pf-confirm="Delete this opportunity?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
