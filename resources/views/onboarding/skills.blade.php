@extends('layouts.guest')

@section('title', 'Add your skills')

@section('content')
    <main class="pf-onboard-flow">
        <div>
            <div class="pf-step">Step 2 of 3</div>
            <h1>How are you starting?</h1>
            <p class="pf-lede">Either path is a good start. You can always add skills later from your profile.</p>

            <form method="POST" action="{{ route('onboarding.skills.starting') }}">
                @csrf
                <input type="hidden" name="starting_as" value="experienced">
                <div class="path">
                    <label>
                        <input type="radio" {{ $startingAs === 'experienced' ? 'checked' : '' }} disabled>
                        <span>
                            <h2>I already have some skills</h2>
                            <p>Choose what you already know for {{ $path->path_name }}. You can also add a skill that is not listed.</p>
                        </span>
                    </label>
                </div>
                <button class="pf-btn" type="submit">{{ $startingAs === 'experienced' ? 'Keep this choice' : 'I already have some skills' }}</button>
            </form>

            <form method="POST" action="{{ route('onboarding.skills.starting') }}" style="margin-top:16px;">
                @csrf
                <input type="hidden" name="starting_as" value="beginner">
                <div class="path">
                    <label>
                        <input type="radio" {{ $startingAs === 'beginner' ? 'checked' : '' }} disabled>
                        <span>
                            <h2>I'm a total beginner</h2>
                            <p>No problem. You will get the full {{ $path->path_name }} roadmap and begin at step 1. Nothing is skipped.</p>
                        </span>
                    </label>
                </div>
                <button class="btn btn-secondary" type="submit">I'm starting from the beginning</button>
            </form>

            @if ($startingAs === 'experienced')
                <form method="POST" action="{{ route('onboarding.skills.store') }}" style="margin-top:24px;">
                    @csrf
                    <label for="name">Add a skill</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Skill name" required>
                    @error('name')
                        <p class="pf-error">{{ $message }}</p>
                    @enderror
                    <button class="pf-btn" type="submit">Add skill</button>
                </form>

                <form method="POST" action="{{ route('onboarding.skills.continue') }}" style="margin-top:24px;">
                    @csrf
                    @php
                        $shownIds = $pathSkills->pluck('id')->all();
                    @endphp
                    <h2>Skills for {{ $path->path_name }}</h2>
                    @error('skill_ids')
                        <p class="pf-error">{{ $message }}</p>
                    @enderror
                    @if ($pathSkills->isEmpty() && $selectedSkills->isEmpty())
                        <p class="muted">No path skills are listed yet. Add one above to continue, or choose “I'm a total beginner”.</p>
                    @else
                        <div class="skill-grid">
                            @foreach ($pathSkills as $skill)
                                <label>
                                    <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" {{ in_array((int) $skill->id, $selectedIds, true) ? 'checked' : '' }}>
                                    <span>{{ $skill->name }}</span>
                                </label>
                            @endforeach
                            @foreach ($selectedSkills as $skill)
                                @if (! in_array($skill->id, $shownIds, true))
                                    <label>
                                        <input type="checkbox" name="skill_ids[]" value="{{ $skill->id }}" checked>
                                        <span>{{ $skill->name }}</span>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    <div class="actions">
                        <a class="btn btn-secondary" href="{{ route('onboarding.show') }}">Back</a>
                        <button class="pf-btn" type="submit">Continue</button>
                    </div>
                </form>
            @else
                <div class="actions" style="margin-top:24px;">
                    <a class="btn btn-secondary" href="{{ route('onboarding.show') }}">Back</a>
                </div>
            @endif
        </div>
    </main>
@endsection
