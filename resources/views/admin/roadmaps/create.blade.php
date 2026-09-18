@extends('admin.layout')

@section('title', 'Create New Path')

@section('content')
    <h2>Create New Path</h2>
    <p class="muted">This career path stays a draft until you add or generate roadmap steps and explicitly publish it. Students cannot see it yet.</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.roadmaps.index') }}">All paths</a>
    </div>

    <form method="POST" action="{{ route('admin.roadmaps.store') }}">
        @csrf

        <div class="field">
            <label for="path_name">Path name</label>
            <input id="path_name" name="path_name" value="{{ old('path_name') }}" maxlength="120" required>
            @error('path_name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="4">{{ old('description') }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="icon">Icon (optional)</label>
            <input id="icon" name="icon" value="{{ old('icon') }}" maxlength="50" placeholder="Optional short label">
            @error('icon') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button class="btn" type="submit">Create draft path</button>
    </form>
@endsection
