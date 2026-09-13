(function () {
    var mount = document.getElementById('pf-hero-canvas');
    if (!mount || typeof THREE === 'undefined') {
        return;
    }

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.z = 6.2;

    var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.8));
    renderer.setClearColor(0x000000, 0);
    mount.appendChild(renderer.domElement);

    var group = new THREE.Group();
    scene.add(group);

    var geo = new THREE.IcosahedronGeometry(1.22, 1);
    var mat = new THREE.MeshStandardMaterial({
        color: 0x9fd4b3,
        metalness: 0.15,
        roughness: 0.35,
        transparent: true,
        opacity: 0.18,
        emissive: 0x1c3a2c,
        emissiveIntensity: 0.18
    });
    group.add(new THREE.Mesh(geo, mat));

    group.add(new THREE.LineSegments(
        new THREE.WireframeGeometry(geo),
        new THREE.LineBasicMaterial({ color: 0xd7c5a0, transparent: true, opacity: 0.48 })
    ));

    scene.add(new THREE.AmbientLight(0xb7c4bb, 0.7));
    var key = new THREE.PointLight(0x9fd4b3, 12, 20);
    key.position.set(4, 3, 5);
    scene.add(key);
    var fill = new THREE.PointLight(0x7aa8c9, 7, 18);
    fill.position.set(-4, -2, 3);
    scene.add(fill);

    var count = 72;
    var positions = new Float32Array(count * 3);
    var drift = [];

    function resetParticle(index, scatter) {
        var angle = Math.random() * Math.PI * 2;
        var spread = 0.45 + Math.random() * 1.35;
        positions[index * 3] = Math.cos(angle) * spread * 0.85;
        positions[index * 3 + 1] = (Math.random() - 0.42) * 1.55;
        positions[index * 3 + 2] = scatter
            ? -3.2 + Math.random() * 3.4
            : -3.5 - Math.random() * 0.7;
    }

    for (var i = 0; i < count; i++) {
        resetParticle(i, true);
        drift.push({
            x: (Math.random() - 0.5) * 0.0035,
            y: 0.0025 + Math.random() * 0.005,
            z: 0.007 + Math.random() * 0.009
        });
    }

    var sparkGeo = new THREE.BufferGeometry();
    sparkGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    var sparks = new THREE.Points(
        sparkGeo,
        new THREE.PointsMaterial({
            color: 0xd4c7ae,
            size: 0.024,
            transparent: true,
            opacity: 0.42,
            depthWrite: false
        })
    );
    sparks.position.z = -0.35;
    scene.add(sparks);

    function size() {
        var w = mount.clientWidth;
        var h = mount.clientHeight;
        camera.aspect = w / Math.max(h, 1);
        camera.updateProjectionMatrix();
        renderer.setSize(w, h, false);
    }

    size();
    window.addEventListener('resize', size);

    function renderStatic() {
        group.rotation.y = 0.4;
        group.rotation.x = 0.1;
        renderer.render(scene, camera);
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        renderStatic();
        return;
    }

    function frame(now) {
        var s = now * 0.001;
        group.rotation.y = s * 0.18;
        group.rotation.x = Math.sin(s * 0.12) * 0.12;

        var attr = sparkGeo.getAttribute('position');
        for (var p = 0; p < count; p++) {
            attr.array[p * 3] += drift[p].x;
            attr.array[p * 3 + 1] += drift[p].y * Math.sin(s * 0.35 + p * 0.2);
            attr.array[p * 3 + 2] += drift[p].z;
            if (attr.array[p * 3 + 2] > 1.8 || Math.abs(attr.array[p * 3]) > 2.5) {
                resetParticle(p, false);
            }
        }
        attr.needsUpdate = true;

        renderer.render(scene, camera);
        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
})();
