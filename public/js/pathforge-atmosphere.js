(function () {
    var canvas = document.getElementById('pf-particles');
    if (!canvas || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    var ctx = canvas.getContext('2d');
    var particles = [];
    var dense = canvas.getAttribute('data-density') === 'dense';

    function resize() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    }

    function seed() {
        particles = [];
        var count = window.innerWidth < 700 ? (dense ? 42 : 28) : (dense ? 90 : 55);
        for (var i = 0; i < count; i++) {
            particles.push({
                x: Math.random() * canvas.width,
                y: Math.random() * canvas.height,
                r: Math.random() * 1.6 + 0.3,
                vx: (Math.random() - 0.5) * 0.16,
                vy: (Math.random() - 0.5) * 0.16,
                a: Math.random() * 0.45 + 0.08,
                warm: Math.random() > 0.82
            });
        }
    }

    function tick() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (var i = 0; i < particles.length; i++) {
            var p = particles[i];
            p.x += p.vx;
            p.y += p.vy;
            if (p.x < 0) p.x = canvas.width;
            if (p.x > canvas.width) p.x = 0;
            if (p.y < 0) p.y = canvas.height;
            if (p.y > canvas.height) p.y = 0;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
            ctx.fillStyle = p.warm
                ? 'rgba(215,197,160,' + p.a + ')'
                : 'rgba(159,212,179,' + p.a + ')';
            ctx.fill();
        }
        requestAnimationFrame(tick);
    }

    resize();
    seed();
    window.addEventListener('resize', function () {
        resize();
        seed();
    });
    tick();
})();
