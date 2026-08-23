@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
    <h2>Admin Dashboard</h2>
    <p>Overview of PathForge users, opportunities, and learning roadmaps.</p>

    <div class="cards">
        <div class="card">
            <div class="label">Total users</div>
            <div class="value">{{ $totalUsers }}</div>
        </div>
        <div class="card">
            <div class="label">Total opportunities</div>
            <div class="value">{{ $totalOpportunities }}</div>
        </div>
        <div class="card">
            <div class="label">Career paths</div>
            <div class="value">{{ $totalCareerPaths }}</div>
        </div>
        <div class="card">
            <div class="label">Roadmap steps</div>
            <div class="value">{{ $totalRoadmapSteps }}</div>
        </div>
        <div class="card">
            <div class="label">Users with a roadmap</div>
            <div class="value">{{ $usersWithRoadmap }}</div>
        </div>
        <div class="card">
            <div class="label">Completed step records</div>
            <div class="value">{{ $completedSteps }}</div>
        </div>
        <div class="card">
            <div class="label">Open opportunities</div>
            <div class="value">{{ $openOpportunities }}</div>
        </div>
        <div class="card">
            <div class="label">Closed opportunities</div>
            <div class="value">{{ $closedOpportunities }}</div>
        </div>
        <div class="card">
            <div class="label">Admin accounts</div>
            <div class="value">{{ $adminCount }}</div>
        </div>
    </div>

    <div class="panel">
        <h2>Recent users</h2>
        @if ($recentUsers->isEmpty())
            <p class="muted">No users yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roadmap</th>
                        <th>XP</th>
                        <th>Level</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentUsers as $user)
                        <tr>
                            <td><a href="{{ route('admin.users.show', $user) }}">{{ $user->name }}</a></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->learningPath->path_name ?? 'None' }}</td>
                            <td>{{ $user->xp ?? 0 }}</td>
                            <td>{{ $user->level ?? 1 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="panel">
        <h2>Recent opportunities</h2>
        @if ($recentOpportunities->isEmpty())
            <p class="muted">No opportunities yet.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Deadline</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOpportunities as $opportunity)
                        <tr>
                            <td>{{ $opportunity->title }}</td>
                            <td>{{ $opportunity->type }}</td>
                            <td>{{ $opportunity->deadline?->toDateString() ?? 'None' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
