<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | PathForge AI</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f5f7;
            color: #222;
            margin: 0;
            padding: 40px 16px;
        }

        .box {
            max-width: 420px;
            margin: 0 auto;
            background: #fff;
            padding: 28px;
            border: 1px solid #ddd;
        }

        h1 {
            font-size: 1.4rem;
            margin: 0 0 8px;
        }

        p {
            margin: 0 0 20px;
            color: #555;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
        }

        .field {
            margin-bottom: 14px;
        }

        .error {
            color: #b00020;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        button {
            width: 100%;
            padding: 10px 14px;
            border: 0;
            background: #1d4ed8;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }

        a {
            color: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Register</h1>
        <p>Create your PathForge AI account.</p>

        <form method="POST" action="{{ url('/register') }}">
            @csrf

            <div class="field">
                <label for="name">Full Name</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required>
                @error('name')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required>
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required>
            </div>

            <button type="submit">Register</button>
        </form>

        <p style="margin-top: 16px;">
            Already have an account? <a href="{{ url('/login') }}">Login</a>
        </p>
    </div>
</body>
</html>
