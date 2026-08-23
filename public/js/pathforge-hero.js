(function () {
    var mount = document.getElementById('pf-hero-canvas');
    if (!mount || typeof THREE === 'undefined') {
        return;
    }
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    var scene = new THREE.Scene();
    var camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    camera.position.z = 6.2;

    var renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.8));
    mount.appendChild(renderer.domElement);

    var group = new THREE.Group();
    scene.add(group);

    var geo = new THREE.IcosahedronGeometry(1.85, 1);
    var mat = new THREE.MeshStandardMaterial({
        color: 0x9fd4b3,
        metalness: 0.15,
        roughness: 0.35,
        transparent: true,
        opacity: 0.18,
        emissive: 0x1c3a2c,
        emissiveIntensity: 0.4
    });
    var mesh = new THREE.Mesh(geo, mat);
    group.add(mesh);

    var wire = new THREE.LineSegments(
        new THREE.WireframeGeometry(geo),
        new THREE.LineBasicMaterial({ color: 0xb7e0c6, transparent: true, opacity: 0.35 })
    );
    group.add(wire);

    var inner = new THREE.Mesh(
        new THREE.OctahedronGeometry(0.62, 0),
        new THREE.MeshStandardMaterial({
            color: 0xd7c5a0,
            metalness: 0.4,
            roughness: 0.2,
            transparent: true,
            opacity: 0.55,
            emissive: 0x3a3220,
            emissiveIntensity: 0.5
        })
    );
    group.add(inner);

    scene.add(new THREE.AmbientLight(0xb7c4bb, 0.7));
    var key = new THREE.PointLight(0x9fd4b3, 18, 20);
    key.position.set(4, 3, 5);
    scene.add(key);
    var fill = new THREE.PointLight(0x7aa8c9, 10, 18);
    fill.position.set(-4, -2, 3);
    scene.add(fill);

    var sparkGeo = new THREE.BufferGeometry();
    var count = 90;
    var positions = new Float32Array(count * 3);
    for (var i = 0; i < count; i++) {
        var r = 2.4 + Math.random() * 1.8;
        var t = Math.random() * Math.PI * 2;
        var p = (Math.random() - 0.5) * Math.PI;
        positions[i * 3] = r * Math.cos(t) * Math.cos(p);
        positions[i * 3 + 1] = r * Math.sin(p);
        positions[i * 3 + 2] = r * Math.sin(t) * Math.cos(p);
    }
    sparkGeo.setAttribute('position', new THREE.BufferAttribute(positions, 3));
    group.add(new THREE.Points(
        sparkGeo,
        new THREE.PointsMaterial({ color: 0xe8f6ee, size: 0.035, transparent: true, opacity: 0.7 })
    ));

    function size() {
        var w = mount.clientWidth;
        var h = mount.clientHeight;
        camera.aspect = w / Math.max(h, 1);
        camera.updateProjectionMatrix();
        renderer.setSize(w, h, false);
    }

    size();
    window.addEventListener('resize', size);

    function frame(t) {
        group.rotation.y = t * 0.00018;
        group.rotation.x = Math.sin(t * 0.00012) * 0.12;
        inner.rotation.y = -t * 0.0004;
        renderer.render(scene, camera);
        requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
})();
