@extends('admin.layout')

@section('title', 'Organizations')

@section('content')
    <h2>Organizations</h2>
    <p>Create an organization and an owner account that can log in to the organization panel.</p>

    <div class="actions">
        <a class="btn" href="{{ route('admin.organizations.create') }}">Add organization</a>
    </div>

    @if ($organizations->isEmpty())
        <p class="muted">No organizations yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Members</th>
                    <th>Opportunities</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($organizations as $organization)
                    <tr>
                        <td>{{ $organization->name }}</td>
                        <td>{{ $organization->email }}</td>
                        <td>{{ $organization->users_count }}</td>
                        <td>{{ $organization->opportunities_count }}</td>
                        <td>{{ ucfirst($organization->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
