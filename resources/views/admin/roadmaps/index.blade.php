@extends('admin.layout')

@section('title', 'Roadmaps')

@section('content')
    <h2>Career paths</h2>
    <p>Open a path to view curated steps or generate an AI draft. Users only see published steps.</p>

    @if ($paths->isEmpty())
        <p class="muted">No career paths found.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Path</th>
                    <th>Source</th>
                    <th>Published steps</th>
                    <th>AI draft</th>
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
                        <td>
                            @if ($path->isAiGenerated())
                                <span class="badge">AI-generated</span>
                            @else
                                <span class="badge">Curated</span>
                            @endif
                        </td>
                        <td>{{ $path->published_roadmap_steps_count }}</td>
                        <td>
                            @if ($path->draft_roadmap_steps_count > 0)
                                {{ $path->draft_roadmap_steps_count }} pending review
                            @else
                                <span class="muted">None</span>
                            @endif
                        </td>
                        <td><a href="{{ route('admin.roadmaps.show', $path) }}">Manage</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
