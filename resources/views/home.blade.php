<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PathForge AI</title>
    <style>
        :root {
            --bg: #0b1220;
            --bg-card: #121a2b;
            --text: #e8eefc;
            --muted: #9aa8c7;
            --accent: #4f8cff;
            --accent-2: #22c55e;
            --border: rgba(255, 255, 255, 0.08);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            font-family: Georgia, "Times New Roman", serif;
            background:
                radial-gradient(circle at top right, rgba(79, 140, 255, 0.18), transparent 36%),
                radial-gradient(circle at bottom left, rgba(34, 197, 94, 0.12), transparent 32%),
                var(--bg);
            color: var(--text);
        }

        .wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 32px 20px 64px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 72px;
        }

        .logo {
            font-size: 1.35rem;
            letter-spacing: 0.04em;
            font-weight: 700;
        }

        .logo span {
            color: var(--accent);
        }

        .nav-actions {
            display: flex;
            gap: 10px;
        }

        .hero {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.28);
        }

        h1 {
            font-size: clamp(2rem, 5vw, 3.2rem);
            line-height: 1.15;
            margin-bottom: 18px;
        }

        p {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 1.05rem;
            line-height: 1.7;
            color: var(--muted);
            max-width: 640px;
            margin-bottom: 32px;
        }

        .cta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        a.button {
            display: inline-block;
            text-decoration: none;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 12px 20px;
            border-radius: 10px;
            border: 1px solid transparent;
        }

        .primary {
            background: var(--accent);
            color: #081018;
        }

        .secondary {
            background: transparent;
            color: var(--text);
            border-color: var(--border);
        }

        .ghost {
            background: rgba(34, 197, 94, 0.12);
            color: var(--accent-2);
            border-color: rgba(34, 197, 94, 0.28);
        }

        @media (max-width: 640px) {
            header {
                flex-direction: column;
                align-items: flex-start;
            }

            .hero {
                padding: 32px 22px;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <div class="logo">PathForge <span>AI</span></div>
            <div class="nav-actions">
                <a class="button secondary" href="{{ url('/login') }}">Login</a>
                <a class="button ghost" href="{{ url('/register') }}">Register</a>
            </div>
        </header>

        <main class="hero">
            <h1>Build Your Career Like a Game.</h1>
            <p>
                PathForge turns skills, quests, and progress into a clear career path.
                Start where you are, complete challenges, and level up toward the role you want.
            </p>
            <div class="cta-row">
                <a class="button primary" href="{{ url('/register') }}">Get Started</a>
                <a class="button secondary" href="{{ url('/login') }}">Login</a>
                <a class="button ghost" href="{{ url('/register') }}">Register</a>
            </div>
        </main>
    </div>
</body>
</html>
