@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <main class="pf-guest">
        <div class="pf-auth">
            <a class="pf-brand" href="{{ url('/') }}">Path<span>Forge</span></a>
            <h1>Login</h1>
            <p class="pf-lede">Sign in to your PathForge AI account.</p>

            @if (session('success'))
                <div class="pf-flash">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ url('/login') }}">
                @csrf
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
                <button class="pf-btn" type="submit">Login</button>
            </form>
            <p class="muted" style="margin-top:16px;">Need an account? <a href="{{ url('/register') }}">Register</a> · <a href="{{ url('/') }}">Home</a></p>
        </div>
    </main>
@endsection
