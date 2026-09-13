@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <main class="pf-guest">
        <div class="pf-auth">
            <a class="pf-brand" href="{{ url('/') }}">Path<span>Forge</span></a>
            <h1>Register</h1>
            <p class="pf-lede">Create your PathForge AI account.</p>

            <form method="POST" action="{{ url('/register') }}" id="register-form">
                @csrf
                <div class="pf-field">
                    <label for="name">Full Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                           pattern="[A-Za-z]+( [A-Za-z]+)*"
                           title="Letters only, with spaces between words. No numbers or symbols."
                           autocomplete="name">
                    @error('name') <div class="pf-error">{{ $message }}</div> @enderror
                    <p id="name-client-error" class="pf-error" hidden>Use letters only, with spaces between words. Numbers and symbols are not allowed.</p>
                </div>
                <div class="pf-field">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                    @error('email') <div class="pf-error">{{ $message }}</div> @enderror
                </div>
                <div class="pf-field">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required>
                    @error('password') <div class="pf-error">{{ $message }}</div> @enderror
                </div>
                <div class="pf-field">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
                <button class="pf-btn" type="submit">Register</button>
            </form>
            <p class="muted" style="margin-top:16px;">Already have an account? <a href="{{ url('/login') }}">Login</a></p>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        (function () {
            var form = document.getElementById('register-form');
            var nameInput = document.getElementById('name');
            var error = document.getElementById('name-client-error');
            if (!form || !nameInput || !error) return;

            function validName(value) {
                return /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(String(value).trim());
            }

            function showValidity() {
                var ok = validName(nameInput.value);
                error.hidden = ok;
                nameInput.setCustomValidity(ok ? '' : error.textContent);
            }

            nameInput.addEventListener('input', showValidity);
            form.addEventListener('submit', function (event) {
                showValidity();
                if (!nameInput.checkValidity()) {
                    event.preventDefault();
                    nameInput.reportValidity();
                }
            });
        })();
    </script>
@endsection
