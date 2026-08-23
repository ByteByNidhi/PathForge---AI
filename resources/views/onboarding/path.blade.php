@extends('layouts.guest')

@section('title', 'Choose your career path')

@section('content')
    <main class="pf-guest">
        <div class="pf-onboard">
            <div class="pf-step">Step 1 of 3</div>
            <h1>Choose your career path</h1>
            <p class="pf-lede">Select one primary path. This becomes your dashboard roadmap.</p>

            @error('path_id')
                <p class="pf-error">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('onboarding.path.store') }}">
                @csrf
                @forelse ($paths as $path)
                    <div class="path">
                        <label>
                            <input type="radio" name="path_id" value="{{ $path->id }}" {{ (int) $selectedPathId === (int) $path->id ? 'checked' : '' }} required>
                            <span>
                                <h2>{{ $path->path_name }}</h2>
                                @if ($path->description)
                                    <p>{{ $path->description }}</p>
                                @endif
                            </span>
                        </label>
                    </div>
                @empty
                    <p class="muted">No career paths are available yet.</p>
                @endforelse
                @if ($paths->isNotEmpty())
                    <button class="pf-btn" type="submit">Continue</button>
                @endif
            </form>
        </div>
    </main>
@endsection
