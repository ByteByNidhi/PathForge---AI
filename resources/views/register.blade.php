@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <main class="pf-guest">
        <div class="pf-auth">
            <a class="pf-brand" href="{{ url('/') }}">Path<span>Forge</span></a>
            <h1>Register</h1>
            <p class="pf-lede">Create your PathForge AI account.</p>

            <form method="POST" action="{{ url('/register') }}">
                @csrf
                <div class="pf-field">
                    <label for="name">Full Name</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                    @error('name') <div class="pf-error">{{ $message }}</div> @enderror
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
