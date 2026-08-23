@extends('admin.layout')

@section('title', $opportunity->exists ? 'Edit opportunity' : 'Add opportunity')

@section('content')
    <h2>{{ $opportunity->exists ? 'Edit opportunity' : 'Add opportunity' }}</h2>
    <p>Required fields: title, organization, type, and application URL.</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.opportunities.index') }}">Back</a>
    </div>

    <form method="POST" action="{{ $opportunity->exists ? route('admin.opportunities.update', $opportunity) : route('admin.opportunities.store') }}">
        @csrf
        @if ($opportunity->exists)
            @method('PUT')
        @endif

        <div class="field">
            <label for="title">Title</label>
            <input id="title" name="title" value="{{ old('title', $opportunity->title) }}" required>
            @error('title') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="organization">Organization</label>
            <input id="organization" name="organization" value="{{ old('organization', $opportunity->organization) }}" required>
            @error('organization') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="type">Type</label>
            <select id="type" name="type" required>
                <option value="">Select type</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected(old('type', $opportunity->type) === $type)>{{ $type }}</option>
                @endforeach
            </select>
            @error('type') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="location">Location</label>
            <input id="location" name="location" value="{{ old('location', $opportunity->location) }}">
            @error('location') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="deadline">Deadline</label>
            <input id="deadline" type="date" name="deadline" value="{{ old('deadline', $opportunity->deadline?->toDateString()) }}">
            @error('deadline') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="application_url">Application URL</label>
            <input id="application_url" type="url" name="application_url" value="{{ old('application_url', $opportunity->application_url) }}" required>
            @error('application_url') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="required_skills">Required skills (comma-separated)</label>
            <textarea id="required_skills" name="required_skills">{{ old('required_skills', $opportunity->required_skills) }}</textarea>
            @error('required_skills') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="eligibility">Eligibility</label>
            <textarea id="eligibility" name="eligibility">{{ old('eligibility', $opportunity->eligibility) }}</textarea>
            @error('eligibility') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description', $opportunity->description) }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button class="btn" type="submit">{{ $opportunity->exists ? 'Save changes' : 'Create opportunity' }}</button>
    </form>
@endsection
