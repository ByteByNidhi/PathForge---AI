@extends('layouts.guest')

@section('title', 'Choose your career path')

@section('content')
    <main class="pf-onboard-flow">
        <div>
            <div class="pf-step">Step 1 of 3</div>
            <h1>Choose your career path</h1>
            <p class="pf-lede">Select one primary path. This becomes your dashboard roadmap.</p>

            @error('path_id')
                <p class="pf-error">{{ $message }}</p>
            @enderror
            @error('requested_path')
                <p class="pf-error">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('onboarding.path.store') }}" id="onboarding-path-form">
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
                <div class="path">
                    <label>
                        <input type="radio" name="path_id" value="other" id="path-other" {{ $otherSelected ? 'checked' : '' }} required>
                        <span>
                            <h2>Other</h2>
                            <p>Request a career path that is not listed yet. We will note it for a future PathForge update.</p>
                        </span>
                    </label>
                </div>
                <div class="pf-field" id="other-path-field" {{ $otherSelected ? '' : 'hidden' }}>
                    <label for="requested_path">Any other career path you're interested in?</label>
                    <input id="requested_path" type="text" name="requested_path" value="{{ old('requested_path', $requestedPath) }}" placeholder="e.g. Machine Learning, Accounting, Digital Marketing" maxlength="120">
                </div>
                <button class="pf-btn" type="submit">Continue</button>
            </form>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        (function () {
            var form = document.getElementById('onboarding-path-form');
            var other = document.getElementById('path-other');
            var field = document.getElementById('other-path-field');
            var input = document.getElementById('requested_path');
            if (!form || !other || !field || !input) return;

            function sync() {
                var show = other.checked;
                field.hidden = !show;
                input.required = show;
            }

            form.querySelectorAll('input[name="path_id"]').forEach(function (radio) {
                radio.addEventListener('change', sync);
            });
            sync();
        })();
    </script>
@endsection
