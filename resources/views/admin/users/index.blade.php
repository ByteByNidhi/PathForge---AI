@extends('admin.layout')

@section('title', 'Users')

@section('content')
    <h2>Users</h2>
    <p>View accounts, selected roadmaps, XP, and level. Users are not deleted from this panel.</p>

    @if ($users->isEmpty())
        <p class="muted">No users yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Roadmap</th>
                    <th>XP</th>
                    <th>Level</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->isAdmin() ? 'Admin' : 'User' }}</td>
                        <td>{{ $user->learningPath->path_name ?? 'None' }}</td>
                        <td>{{ $user->xp ?? 0 }}</td>
                        <td>{{ $user->level ?? 1 }}</td>
                        <td><a href="{{ route('admin.users.show', $user) }}">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
