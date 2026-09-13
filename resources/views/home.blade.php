@extends('layouts.guest')

@section('title', 'PathForge')
@section('body_class', 'pf-landing')

@section('content')
<div class="pf-wrap">
    <header class="pf-sitehead">
        <a class="pf-wordmark" href="{{ url('/') }}">Path<span>Forge</span></a>
        <button class="pf-sitehead__toggle" type="button" data-pf-public-menu aria-label="Open menu">☰</button>
        <nav class="pf-sitehead__nav" aria-label="Landing">
            <a class="pf-textlink" href="#features">Product</a>
            <a class="pf-textlink" href="#how-it-works">How it works</a>
            <a class="pf-btn pf-btn--ghost" href="{{ url('/login') }}">Login</a>
            <a class="pf-btn" href="{{ url('/register') }}">Get started</a>
        </nav>
    </header>

    <section class="pf-hero pf-hero--brand">
        <div class="pf-hero__copy">
            <p class="pf-hero__mark">Path<span>Forge</span></p>
            <h1 class="pf-hero__tag">Forge the path</h1>
            <p>Skills, roadmaps, opportunities, and AI guidance in one calm operating system for your career — premium enough for daily use, clear enough for real decisions.</p>
            <div class="pf-row pf-hero-cta">
                <a class="pf-btn" href="{{ url('/register') }}">Start your path</a>
                <a class="pf-btn pf-btn--ghost" href="#features">See the system</a>
            </div>
        </div>
        <div class="pf-hero-visual" aria-hidden="true">
            <div id="pf-hero-canvas" class="pf-hero-canvas"></div>
        </div>
    </section>

    <section id="features" class="pf-section">
        <div class="pf-kicker">The system</div>
        <h2>Everything that should already be connected.</h2>
        <p class="pf-lede">Roadmaps, opportunities, skills, and a career assistant — presented as one product, not a pile of tools.</p>
        <div class="pf-features">
            <article class="pf-feature"><b>01</b><h3>Discover</h3><p>Internships, hackathons, scholarships, and research matched to the skills you actually have.</p></article>
            <article class="pf-feature"><b>02</b><h3>Build</h3><p>Follow a structured roadmap of skills and milestones instead of guessing what to learn next.</p></article>
            <article class="pf-feature"><b>03</b><h3>Progress</h3><p>XP, levels, and achievements mark real work — quietly, professionally, without turning career growth into a game.</p></article>
            <article class="pf-feature"><b>04</b><h3>Advise</h3><p>AI Studio answers from your PathForge profile: your path, skills, and completed steps.</p></article>
        </div>
    </section>

    <section id="how-it-works" class="pf-section">
        <div class="pf-kicker">Progression</div>
        <h2>See the next move.</h2>
        <div class="pf-path">
            <div><span>1</span><small>Choose a path</small></div>
            <div><span>2</span><small>Add skills</small></div>
            <div><span>3</span><small>Complete steps</small></div>
            <div><span>4</span><small>Apply</small></div>
            <div><span>5</span><small>Grow</small></div>
        </div>
    </section>

    <section class="pf-cta">
        <div class="pf-kicker">Begin</div>
        <h2>Make the next move count.</h2>
        <p class="muted">Create an account, pick a career path, and let PathForge keep the rest in view.</p>
        <a class="pf-btn" href="{{ url('/register') }}">Enter PathForge</a>
    </section>

    <footer class="pf-footer">
        <span>© {{ date('Y') }} PathForge</span>
        <span>Build your path. Forge what’s next.</span>
    </footer>
</div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
<script src="{{ asset('js/pathforge-hero.js') }}"></script>
@endsection
