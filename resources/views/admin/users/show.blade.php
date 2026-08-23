@extends('admin.layout')

@section('title', $user->name)

@section('content')
    <h2>{{ $user->name }}</h2>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.users.index') }}">All users</a>
    </div>

    <div class="panel">
        <p><strong>Email:</strong> {{ $user->email }}</p>
        <p><strong>Role:</strong> {{ $user->isAdmin() ? 'Admin' : 'User' }}</p>
        <p><strong>Selected roadmap:</strong> {{ $user->learningPath->path_name ?? 'None' }}</p>
        <p><strong>XP:</strong> {{ $user->xp ?? 0 }}</p>
        <p><strong>Level:</strong> {{ $user->level ?? 1 }}</p>
        <p><strong>Roadmap progress:</strong>
            @if ($user->learningPath)
                {{ $completedSteps }} / {{ $totalSteps }} steps completed
            @else
                No roadmap selected
            @endif
        </p>
        <p><strong>Joined:</strong> {{ $user->created_at?->toDayDateTimeString() }}</p>
    </div>

    <div class="panel">
        <h2>Skills</h2>
        @if ($user->skills->isEmpty())
            <p class="muted">No skills added.</p>
        @else
            <p>{{ $user->skills->pluck('name')->join(', ') }}</p>
        @endif
    </div>
@endsection
