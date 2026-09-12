@extends('organization.layout')

@section('title', $opportunity->exists ? 'Edit opportunity' : 'Create opportunity')

@section('content')
    <h2>{{ $opportunity->exists ? 'Edit opportunity' : 'Create opportunity' }}</h2>
    <p>Save as a draft or submit for admin review. Students only see approved listings.</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('organization.opportunities.index') }}">Back</a>
    </div>

    <form method="POST" action="{{ $opportunity->exists ? route('organization.opportunities.update', $opportunity) : route('organization.opportunities.store') }}">
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
            <label for="description">Description</label>
            <textarea id="description" name="description" required>{{ old('description', $opportunity->description) }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
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
            <input id="application_url" type="url" name="application_url" value="{{ old('application_url', $opportunity->application_url) }}" placeholder="https://">
            @error('application_url') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="eligibility">Eligibility</label>
            <textarea id="eligibility" name="eligibility">{{ old('eligibility', $opportunity->eligibility) }}</textarea>
            @error('eligibility') <div class="error">{{ $message }}</div> @enderror
        </div>

        <fieldset class="field">
            <legend>Required skills</legend>
            <p class="muted">Choose existing PathForge skills. At least one is required.</p>
            @error('skill_ids') <div class="error">{{ $message }}</div> @enderror
            <div class="chips" style="display:block;">
                @foreach ($skills as $skill)
                    <label class="chip" style="display:inline-flex;gap:6px;align-items:center;">
                        <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" @checked(in_array($skill->id, array_map('intval', $selectedSkillIds), true))>
                        {{ $skill->name }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="actions">
            <button class="btn btn-secondary" type="submit" name="intent" value="draft">Save Draft</button>
            <button class="btn" type="submit" name="intent" value="submit">{{ $opportunity->isRejected() ? 'Resubmit for Review' : 'Submit for Review' }}</button>
        </div>
    </form>
@endsection
