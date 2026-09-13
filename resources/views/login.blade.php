@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <main class="pf-auth-split">
        <aside class="pf-auth-split__brand">
            <a class="pf-wordmark" href="{{ url('/') }}">Path<span>Forge</span></a>
            <div>
                <h1>Path<br>Forge</h1>
                <p>Welcome back. Continue the work already in motion.</p>
            </div>
            <p class="muted">A career operating system — not another generic account wall.</p>
        </aside>
        <section class="pf-auth-split__form">
            <div class="pf-auth-panel">
                <p class="pf-kicker">Sign in</p>
                <h2>Login</h2>
                <p class="pf-lede">Use the email and password for your PathForge account.</p>

                @if (session('success'))
                    <div class="pf-flash">{{ session('success') }}</div>
                @endif

                <form method="POST" action="{{ url('/login') }}" style="margin-top:28px;">
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
                <p class="muted" style="margin-top:20px;">Need an account? <a href="{{ url('/register') }}">Register</a> · <a href="{{ url('/') }}">Home</a></p>
            </div>
        </section>
    </main>
@endsection
