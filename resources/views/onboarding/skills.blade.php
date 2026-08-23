@extends('layouts.guest')

@section('title', 'Add your skills')

@section('content')
    <main class="pf-guest">
        <div class="pf-onboard">
            <div class="pf-step">Step 2 of 3</div>
            <h1>Add your skills</h1>
            <p class="pf-lede">Select skills you already have, or add a new one. You can also add more later from your profile.</p>

            <form method="POST" action="{{ route('onboarding.skills.store') }}">
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
                <h2>Select skills</h2>
                @php $shownIds = $catalog->pluck('id')->all(); @endphp
                @if ($catalog->isEmpty() && $selectedSkills->isEmpty())
                    <p class="muted">No skills in the catalog yet. Add one above to continue.</p>
                @else
                    <div class="skill-grid">
                        @foreach ($catalog as $skill)
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
        </div>
    </main>
@endsection
