@extends('admin.layout')

@section('title', $step->exists ? 'Edit step' : 'Add step')

@section('content')
    <h2>{{ $step->exists ? 'Edit step' : 'Add step' }}</h2>
    <p class="muted">{{ $path->path_name }}</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.show', $path) }}">Back</a>
    </div>

    <form method="POST" action="{{ $step->exists ? route('admin.roadmaps.steps.update', [$path, $step]) : route('admin.roadmaps.steps.store', $path) }}">
        @csrf
        @if ($step->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label for="step_no">Step number</label>
            <input id="step_no" type="number" min="1" name="step_no" value="{{ old('step_no', $step->step_no) }}" required>
            @error('step_no') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="title">Title</label>
            <input id="title" name="title" value="{{ old('title', $step->title) }}" required>
            @error('title') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="xp_reward">XP reward</label>
            <input id="xp_reward" type="number" min="0" name="xp_reward" value="{{ old('xp_reward', $step->xp_reward) }}" required>
            @error('xp_reward') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button class="btn" type="submit">{{ $step->exists ? 'Save changes' : 'Add step' }}</button>
    </form>
@endsection
