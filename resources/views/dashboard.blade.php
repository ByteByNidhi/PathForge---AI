<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | PathForge AI</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f5f7;
            color: #222;
            margin: 0;
            padding: 40px 16px;
        }

        .box {
            max-width: 520px;
            margin: 0 auto;
            background: #fff;
            padding: 28px;
            border: 1px solid #ddd;
        }

        h1 {
            font-size: 1.4rem;
            margin: 0 0 16px;
        }

        dl {
            margin: 0 0 24px;
        }

        dt {
            font-weight: bold;
            margin-top: 12px;
        }

        dd {
            margin: 4px 0 0;
        }

        button {
            padding: 10px 14px;
            border: 0;
            background: #333;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="box">
        <h1>Dashboard</h1>

        <dl>
            <dt>Name</dt>
            <dd>{{ $user->name }}</dd>

            <dt>Email</dt>
            <dd>{{ $user->email }}</dd>

            <dt>Level</dt>
            <dd>{{ $user->level ?? 1 }}</dd>

            <dt>XP</dt>
            <dd>{{ $user->xp ?? 0 }}</dd>
        </dl>

        <form method="POST" action="{{ url('/logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</body>
</html>
