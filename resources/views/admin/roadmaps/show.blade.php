@extends('admin.layout')

@section('title', $path->path_name)

@section('content')
    <h2>{{ $path->path_name }}</h2>
    @if ($path->description)
        <p>{{ $path->description }}</p>
    @endif

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.index') }}">All paths</a>
        <a class="btn" href="{{ route('admin.roadmaps.steps.create', $path) }}">Add roadmap step</a>
    </div>

    @if ($steps->isEmpty())
        <p class="muted">No steps yet.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Step</th>
                    <th>Title</th>
                    <th>XP</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($steps as $step)
                    <tr>
                        <td>{{ $step->step_no }}</td>
                        <td>{{ $step->title }}</td>
                        <td>{{ $step->xp_reward }}</td>
                        <td>
                            <a href="{{ route('admin.roadmaps.steps.edit', [$path, $step]) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('admin.roadmaps.steps.destroy', [$path, $step]) }}" onsubmit="return confirm('Delete this step? Related progress records for this step will also be removed.');">
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
