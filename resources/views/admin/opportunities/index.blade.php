@extends('admin.layout')

@section('title', 'Opportunities')

@section('content')
    <h2>Opportunities</h2>
    <p>Manage listings shown in the Opportunity Hub.</p>

    <div class="actions">
        <a class="btn" href="{{ route('admin.opportunities.create') }}">Add opportunity</a>
    </div>

    @if ($opportunities->isEmpty())
        <p class="muted">No opportunities yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Organization</th>
                    <th>Type</th>
                    <th>Location</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($opportunities as $opportunity)
                    <tr>
                        <td>{{ $opportunity->title }}</td>
                        <td>{{ $opportunity->organization }}</td>
                        <td>{{ $opportunity->type }}</td>
                        <td>{{ $opportunity->location ?? '—' }}</td>
                        <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        <td>
                            <a href="{{ route('admin.opportunities.edit', $opportunity) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('admin.opportunities.destroy', $opportunity) }}" onsubmit="return confirm('Delete this opportunity?');">
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
