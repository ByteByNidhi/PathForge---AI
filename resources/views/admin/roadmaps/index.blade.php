@extends('admin.layout')

@section('title', 'Roadmaps')

@section('content')
    <h2>Career paths</h2>
    <p>Open a path to view and manage its roadmap steps. User progress is kept when you edit a step.</p>

    @if ($paths->isEmpty())
        <p class="muted">No career paths found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Path</th>
                    <th>Steps</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($paths as $path)
                    <tr>
                        <td>
                            <strong>{{ $path->path_name }}</strong>
                            @if ($path->description)
                                <div class="muted">{{ $path->description }}</div>
                            @endif
                        </td>
                        <td>{{ $path->roadmap_steps_count }}</td>
                        <td><a href="{{ route('admin.roadmaps.show', $path) }}">Manage steps</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
