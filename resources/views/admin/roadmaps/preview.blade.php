@extends('admin.layout')

@section('title', 'Review AI draft')

@section('content')
    <h2>Review AI draft</h2>
    <p class="muted">{{ $path->path_name }} — unpublished. Users cannot see these steps.</p>

    @if ($path->roadmap_draft_title)
        <h3>{{ $path->roadmap_draft_title }}</h3>
    @endif
    @if ($path->roadmap_draft_description)
        <p>{{ $path->roadmap_draft_description }}</p>
    @endif

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.show', $path) }}">Back</a>
    </div>

    @if ($hasStudentProgress)
        <p>Publishing is blocked because users already have progress on the live roadmap. Existing progress was not changed.</p>
    @else
        <p>Publishing replaces the live published steps with this draft. Curated or previous AI steps with no user progress will be replaced.</p>
        <form method="POST" action="{{ route('admin.roadmaps.publish', $path) }}">
            @csrf
            <button class="btn" type="submit">Publish</button>
        </form>
    @endif

    <table style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Step</th>
                <th>Title</th>
                <th>Description</th>
                <th>XP</th>
                <th>Skills</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($draftSteps as $step)
                <tr>
                    <td>{{ $step->step_no }}</td>
                    <td>{{ $step->title }}</td>
                    <td>{{ $step->description }}</td>
                    <td>{{ $step->xp_reward }}</td>
                    <td>{{ $step->skills->pluck('name')->implode(', ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
