@extends('admin.layout')

@section('title', $path->path_name)

@section('content')
    <h2>{{ $path->path_name }}</h2>
    @if (! $path->is_published)
        <p><span class="badge">Draft — not visible to students</span></p>
    @endif
    @if ($path->description)
        <p>{{ $path->description }}</p>
    @endif

    <p>
        @if ($path->isAiGenerated())
            <span class="badge">Live source: AI-generated</span>
        @else
            <span class="badge">Live source: Curated</span>
        @endif
        @if ($path->roadmap_generated_at)
            <span class="muted">Last generated {{ $path->roadmap_generated_at->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</span>
        @endif
    </p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.index') }}">All paths</a>
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.steps.create', $path) }}">Add roadmap step</a>
        @if (! $path->is_published)
            <form class="inline-form" method="POST" action="{{ route('admin.roadmaps.publish', $path) }}">
                @csrf
                <button class="btn" type="submit">Publish path</button>
            </form>
        @endif
    </div>

    <section class="pf-card" style="margin: 20px 0;">
        <h3>Generate with AI</h3>
        <p class="muted">Gemini drafts unpublished steps for review. Users never see a draft. Generation does not run for users.</p>
        @if ($hasStudentProgress)
            <p>Users already have progress on the live roadmap. You can still generate a draft, but publishing is blocked so their progress is not replaced.</p>
        @endif
        <form method="POST" action="{{ route('admin.roadmaps.generate', $path) }}">
            @csrf
            <label>
                <input type="checkbox" name="beginner" value="1" @checked($isBeginnerPath)>
                Generate a complete beginner / foundation roadmap
            </label>
            <div class="actions" style="margin-top: 12px;">
                <button class="btn" type="submit">{{ $draftSteps->isNotEmpty() ? 'Regenerate AI draft' : 'Generate with AI' }}</button>
                @if ($draftSteps->isNotEmpty())
                    <a class="btn btn-secondary" href="{{ route('admin.roadmaps.preview', $path) }}">Review draft</a>
                @endif
            </div>
        </form>
    </section>

    @if ($draftSteps->isNotEmpty())
        <h3>Draft steps</h3>
        <p class="muted">Unpublished. Students cannot see these until you publish the path.</p>
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
                @foreach ($draftSteps as $step)
                    <tr>
                        <td>{{ $step->step_no }}</td>
                        <td>
                            {{ $step->title }}
                            @if ($step->skills->isNotEmpty())
                                <div class="muted">{{ $step->skills->pluck('name')->implode(', ') }}</div>
                            @endif
                        </td>
                        <td>{{ $step->xp_reward }}</td>
                        <td>
                            <a href="{{ route('admin.roadmaps.steps.edit', [$path, $step]) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('admin.roadmaps.steps.destroy', [$path, $step]) }}" data-pf-confirm="Delete this draft step?">
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

    <h3>Published steps</h3>
    <p class="muted">This is what users currently see.</p>

    @if ($steps->isEmpty())
        <p class="muted">No published steps yet.</p>
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
                        <td>
                            {{ $step->title }}
                            @if ($step->skills->isNotEmpty())
                                <div class="muted">{{ $step->skills->pluck('name')->implode(', ') }}</div>
                            @endif
                        </td>
                        <td>{{ $step->xp_reward }}</td>
                        <td>
                            <a href="{{ route('admin.roadmaps.steps.edit', [$path, $step]) }}">Edit</a>
                            <form class="inline-form" method="POST" action="{{ route('admin.roadmaps.steps.destroy', [$path, $step]) }}" data-pf-confirm="Delete this step? Related progress records for this step will also be removed.">
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
