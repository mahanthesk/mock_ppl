// intro_animation.js

// 1. Scene Setup
const introScene = new THREE.Scene();
introScene.fog = new THREE.FogExp2(0x050505, 0.002);

// 2. Camera
const introCamera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);

// Responsive Camera Distance
function getResponsiveZ() {
    return window.innerWidth < 768 ? 25 : 15; // Move back further on mobile (was 15)
}
const introCameraStartY = -2.5; // Visual center of content
introCamera.position.y = introCameraStartY;
introCamera.position.z = getResponsiveZ();

// 3. Renderer
const introRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
introRenderer.setSize(window.innerWidth, window.innerHeight);
introRenderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
introRenderer.shadowMap.enabled = true;
introRenderer.shadowMap.type = THREE.PCFSoftShadowMap;
introRenderer.toneMapping = THREE.ACESFilmicToneMapping;
introRenderer.toneMappingExposure = 1.25;

// Append to the specific container
const container = document.getElementById('intro-canvas-container');
if (container) {
    container.appendChild(introRenderer.domElement);
}

// 4. Lighting
const introAmbientLight = new THREE.AmbientLight(0xffffff, 0.2);
introScene.add(introAmbientLight);

const introSpotLight = new THREE.SpotLight(0xffaa00, 2);
introSpotLight.position.set(-50, 10, 20); // Start far left
introSpotLight.angle = Math.PI / 6;
introSpotLight.penumbra = 1;
introSpotLight.decay = 2;
introSpotLight.distance = 200;
introSpotLight.castShadow = true;
introSpotLight.intensity = 0; // Start dark
introScene.add(introSpotLight);

const introPointLight = new THREE.PointLight(0xffd700, 1, 100);
introPointLight.position.set(-20, 5, 5);
introScene.add(introPointLight);

// 5. Particles
const introParticlesGeometry = new THREE.BufferGeometry();
const introParticlesCount = 700;
const introPosArray = new Float32Array(introParticlesCount * 3);

for (let i = 0; i < introParticlesCount * 3; i++) {
    introPosArray[i] = (Math.random() - 0.5) * 50;
}

introParticlesGeometry.setAttribute('position', new THREE.BufferAttribute(introPosArray, 3));

const introParticlesMaterial = new THREE.PointsMaterial({
    size: 0.15,
    color: 0xffd700,
    transparent: true,
    opacity: 0.8,
    blending: THREE.AdditiveBlending
});

const introParticlesMesh = new THREE.Points(introParticlesGeometry, introParticlesMaterial);
introScene.add(introParticlesMesh);

// 6. Text Loading & Animation
const introFontLoader = new THREE.FontLoader();
const introTextMaterial = new THREE.MeshStandardMaterial({
    color: 0x111111,
    emissive: 0x000000,
    metalness: 0.9,
    roughness: 0.2,
});

// New Materials for Founder Box (Start Dark for Delay Effect)
const introCornerMaterial = new THREE.MeshStandardMaterial({
    color: 0x111111, // Start Dark
    emissive: 0x000000,
    metalness: 1.0,
    roughness: 0.2
});
const introBorderMaterial = new THREE.MeshStandardMaterial({
    color: 0x111111, // Start Dark
    emissive: 0x000000,
    metalness: 0.8,
    roughness: 0.4,
    transparent: true,
    opacity: 0.3
});

let introMonogramMesh, introWordmarkMesh;
let introIsIdle = true;
let introAnimationId;

introFontLoader.load('https://unpkg.com/three@0.128.0/examples/fonts/helvetiker_bold.typeface.json', (font) => {

    // Helper to create solid cinematic corners (Meshes)
    function createSolidCornerBrackets(w, h, length = 0.8, thickness = 0.08) {
        const group = new THREE.Group();
        // Use Global Material
        const goldMat = introCornerMaterial;

        const x = w / 2;
        const y = h / 2;

        // Helper to add a box
        function addBox(bx, by, bw, bh) {
            const geo = new THREE.BoxGeometry(bw, bh, thickness);
            const mesh = new THREE.Mesh(geo, goldMat);
            mesh.position.set(bx, by, 0);
            mesh.castShadow = true;
            mesh.receiveShadow = true;
            group.add(mesh);
        }

        // Top Left
        addBox(-x + length / 2, y, length, thickness); // Horz
        addBox(-x, y - length / 2, thickness, length); // Vert

        // Top Right
        addBox(x - length / 2, y, length, thickness);
        addBox(x, y - length / 2, thickness, length);

        // Bottom Left
        addBox(-x + length / 2, -y, length, thickness);
        addBox(-x, -y + length / 2, thickness, length);

        // Bottom Right
        addBox(x - length / 2, -y, length, thickness);
        addBox(x, -y + length / 2, thickness, length);

        return group;
    }

    // Helper for subtle solid border
    function createSolidBorder(w, h, thickness = 0.02) {
        const group = new THREE.Group();
        // Use Global Material
        const borderMat = introBorderMaterial;

        const x = w / 2;
        const y = h / 2;

        // Top/Bottom
        const horzGeo = new THREE.BoxGeometry(w, thickness, thickness);
        const top = new THREE.Mesh(horzGeo, borderMat);
        top.position.set(0, y, 0);
        group.add(top);

        const bot = new THREE.Mesh(horzGeo, borderMat);
        bot.position.set(0, -y, 0);
        group.add(bot);

        // Left/Right
        const vertGeo = new THREE.BoxGeometry(thickness, h, thickness);
        const left = new THREE.Mesh(vertGeo, borderMat);
        left.position.set(-x, 0, 0);
        group.add(left);

        const right = new THREE.Mesh(vertGeo, borderMat);
        right.position.set(x, 0, 0);
        group.add(right);

        return group;
    }

    // Monogram "PPL"
    const monoGeo = new THREE.TextGeometry('PPL', {
        font: font,
        size: 5,
        height: 1,
        curveSegments: 12,
        bevelEnabled: true,
        bevelThickness: 0.1,
        bevelSize: 0.1,
        bevelOffset: 0,
        bevelSegments: 5
    });
    monoGeo.center();
    introMonogramMesh = new THREE.Mesh(monoGeo, introTextMaterial);
    introMonogramMesh.position.y = 2;
    introMonogramMesh.castShadow = true;
    introMonogramMesh.receiveShadow = true;
    introScene.add(introMonogramMesh);

    // Wordmark Logic (Responsive)
    if (window.innerWidth < 768) {
        // Mobile: 2 Lines (Pretium / Premier League)
        const lines = ['Pretium', 'Premier League'];
        const lineMeshes = [];

        lines.forEach((line, index) => {
            const lineGeo = new THREE.TextGeometry(line, {
                font: font,
                size: 1.0,
                height: 0.2,
                bevelEnabled: true,
                bevelThickness: 0.02,
                bevelSize: 0.02,
                bevelOffset: 0,
                bevelSegments: 5
            });
            lineGeo.center();
            const mesh = new THREE.Mesh(lineGeo, introTextMaterial);

            // Stack them vertically
            mesh.position.y = -2 - (index * 1.5);

            mesh.castShadow = true;
            mesh.receiveShadow = true;
            introScene.add(mesh);
            lineMeshes.push(mesh);
        });
        introWordmarkMesh = lineMeshes[0];

        // Founder Group (Mobile)
        const founderGroup = new THREE.Group();
        founderGroup.position.y = -6.5;
        introScene.add(founderGroup);

        // Background Panel (Wider) - Using Standard Material for Lighting Sync
        const panelW_Mob = 8;
        const panelH_Mob = 3;
        const panelGeo = new THREE.PlaneGeometry(panelW_Mob, panelH_Mob);
        const panelMat = new THREE.MeshStandardMaterial({
            color: 0x000000,
            transparent: true,
            opacity: 0.85,
            side: THREE.DoubleSide,
            roughness: 0.9,
            metalness: 0.1
        });
        const panelMesh = new THREE.Mesh(panelGeo, panelMat);
        panelMesh.position.z = -0.1;
        panelMesh.receiveShadow = true;
        founderGroup.add(panelMesh);

        // Solid Cinematic Corners
        const corners = createSolidCornerBrackets(panelW_Mob, panelH_Mob, 0.8);
        corners.position.z = 0.05; // Slightly in front
        founderGroup.add(corners);

        // Solid Subtle Border
        const border = createSolidBorder(panelW_Mob, panelH_Mob);
        border.position.z = 0.05;
        founderGroup.add(border);


        // Founder & Chairman Text (Mobile)
        const founderTitleGeo = new THREE.TextGeometry('Founder & Chairman', {
            font: font,
            size: 0.45,
            height: 0.1,
            bevelEnabled: true,
            bevelThickness: 0.01,
            bevelSize: 0.01,
            bevelOffset: 0,
            bevelSegments: 3
        });
        founderTitleGeo.center();
        const founderTitleMesh = new THREE.Mesh(founderTitleGeo, introTextMaterial);
        founderTitleMesh.position.y = 0.6;
        founderTitleMesh.castShadow = true;
        founderTitleMesh.receiveShadow = true;
        founderGroup.add(founderTitleMesh);

        const founderNameGeo = new THREE.TextGeometry('Sandeep Gowda', {
            font: font,
            size: 0.7,
            height: 0.1,
            bevelEnabled: true,
            bevelThickness: 0.02,
            bevelSize: 0.01,
            bevelOffset: 0,
            bevelSegments: 4
        });
        founderNameGeo.center();
        const founderNameMesh = new THREE.Mesh(founderNameGeo, introTextMaterial);
        founderNameMesh.position.y = -0.6;
        founderNameMesh.castShadow = true;
        founderNameMesh.receiveShadow = true;
        founderGroup.add(founderNameMesh);

    } else {
        // Desktop: Single Line
        const wordGeo = new THREE.TextGeometry('Pretium Premier League', {
            font: font,
            size: 1.3,
            height: 0.2,
            bevelEnabled: true,
            bevelThickness: 0.02,
            bevelSize: 0.02,
            bevelOffset: 0,
            bevelSegments: 5
        });

        wordGeo.center();
        introWordmarkMesh = new THREE.Mesh(wordGeo, introTextMaterial);
        introWordmarkMesh.position.y = -3;
        introWordmarkMesh.castShadow = true;
        introWordmarkMesh.receiveShadow = true;
        introScene.add(introWordmarkMesh);

        // Founder Group (Desktop)
        const founderGroup = new THREE.Group();
        founderGroup.position.y = -7;
        introScene.add(founderGroup);

        // Background Panel (Wider) - Standard Material
        const panelW_Desk = 11;
        const panelH_Desk = 3.5;
        const panelGeo = new THREE.PlaneGeometry(panelW_Desk, panelH_Desk);
        const panelMat = new THREE.MeshStandardMaterial({
            color: 0x000000,
            transparent: true,
            opacity: 0.85,
            side: THREE.DoubleSide,
            roughness: 0.9,
            metalness: 0.1
        });
        const panelMesh = new THREE.Mesh(panelGeo, panelMat);
        panelMesh.position.z = -0.1;
        panelMesh.receiveShadow = true;
        founderGroup.add(panelMesh);

        // Solid Cinematic Corners
        const corners = createSolidCornerBrackets(panelW_Desk, panelH_Desk, 1.0);
        corners.position.z = 0.05;
        founderGroup.add(corners);

        // Solid Subtle Border
        const border = createSolidBorder(panelW_Desk, panelH_Desk);
        border.position.z = 0.05;
        founderGroup.add(border);

        // Founder & Chairman Text (Desktop)
        const founderTitleGeo = new THREE.TextGeometry('Founder & Chairman', {
            font: font,
            size: 0.6,
            height: 0.1,
            bevelEnabled: true,
            bevelThickness: 0.01,
            bevelSize: 0.01,
            bevelOffset: 0,
            bevelSegments: 3
        });
        founderTitleGeo.center();
        const founderTitleMesh = new THREE.Mesh(founderTitleGeo, introTextMaterial);
        founderTitleMesh.position.y = 0.8;
        founderTitleMesh.castShadow = true;
        founderTitleMesh.receiveShadow = true;
        founderGroup.add(founderTitleMesh);

        const founderNameGeo = new THREE.TextGeometry('Sandeep Gowda', {
            font: font,
            size: 0.9,
            height: 0.15,
            bevelEnabled: true,
            bevelThickness: 0.02,
            bevelSize: 0.01,
            bevelOffset: 0,
            bevelSegments: 4
        });
        founderNameGeo.center();
        const founderNameMesh = new THREE.Mesh(founderNameGeo, introTextMaterial);
        founderNameMesh.position.y = -0.8;
        founderNameMesh.castShadow = true;
        founderNameMesh.receiveShadow = true;
        founderGroup.add(founderNameMesh);
    }

    // Start Animation Sequence
    runIntroSequence();
});

// Resize Handler
window.addEventListener('resize', () => {
    introCamera.aspect = window.innerWidth / window.innerHeight;
    introCamera.updateProjectionMatrix();
    introRenderer.setSize(window.innerWidth, window.innerHeight);

    // Adjust camera Z on resize if needed (optional, or keep initial)
    // introCamera.position.z = getResponsiveZ(); 
});

// Animation Loop
const introClock = new THREE.Clock();
function introAnimate() {
    introAnimationId = requestAnimationFrame(introAnimate);
    const elapsedTime = introClock.getElapsedTime();

    if (introParticlesMesh) {
        introParticlesMesh.rotation.y = elapsedTime * 0.05;
        introParticlesMesh.rotation.x = elapsedTime * 0.02;
    }

    if (introIsIdle) {
        introCamera.position.y = introCameraStartY + Math.sin(elapsedTime * 0.5) * 0.05; // Oscillate around center
    }

    introRenderer.render(introScene, introCamera);
}
introAnimate();

// GSAP Sequence (Optimized for ~6-8s)
function runIntroSequence() {
    const tl = gsap.timeline({
        defaults: { ease: "power2.inOut" },
        onComplete: onIntroComplete // Callback when done
    });

    tl.add("start", 0);

    // Phase 1: Fast Sweep & Reveal (0s - 3s)
    tl.to(introSpotLight, { intensity: 4, duration: 1.5 }, "start")
        .to(introSpotLight.position, { x: 50, duration: 3.5, ease: "slow(0.7, 0.7, false)" }, "start");

    // Material turn to Gold
    // Material turn to Gold
    if (introMonogramMesh) {
        // Text turns gold
        tl.to(introTextMaterial.color, { r: 1, g: 0.84, b: 0, duration: 2 }, "start+=0.5");
        tl.to(introTextMaterial.emissive, { r: 0.26, g: 0.13, b: 0, duration: 2 }, "start+=0.5");

        // Founder Box Elements turn gold with REDUCED DELAY (start+=0.7)
        tl.to(introCornerMaterial.color, { r: 0.83, g: 0.68, b: 0.21, duration: 1 }, "start+=0.7"); // D4AF37
        tl.to(introCornerMaterial.emissive, { r: 0.2, g: 0.13, b: 0, duration: 1 }, "start+=0.7"); // 332200

        tl.to(introBorderMaterial.color, { r: 0.83, g: 0.68, b: 0.21, duration: 1 }, "start+=0.7");
    }

    // Phase 2: Reverse Sweep & Quick Zoom (3s - 6s)
    tl.to(introSpotLight.position, { x: -50, duration: 2.5, ease: "power2.inOut" }, ">-1");

    // Zoom out faster
    tl.to(introCamera.position, {
        z: 22,
        duration: 3,
        ease: "power1.out"
    }, "start+=2.5");

    // Phase 3: Fade Out (6s - 7.5s)
    // Pre-show hero behind the curtain so it's ready when we fade out
    tl.call(() => {
        const hero = document.getElementById('hero');
        if (hero) {
            hero.style.display = 'flex';
            // Force reflow
            void hero.offsetWidth;
            hero.classList.add('visible');
        }
    }, null, "start+=5.5");

    const introContainer = document.getElementById('intro-canvas-container');

    tl.to(introScene.fog, { density: 0.5, duration: 1.5, ease: "power1.in" }, "start+=6");
    tl.to(introAmbientLight, { intensity: 0, duration: 1.5 }, "start+=6");
    tl.to(introSpotLight, { intensity: 0, duration: 1.5 }, "start+=6");

    // Fade out the CONTAINER (which includes black bg) instead of just the canvas
    if (introContainer) {
        tl.to(introContainer, { opacity: 0, duration: 2, ease: "power1.in" }, "start+=6");
    }
}

function onIntroComplete() {
    // 1. cleanup WebGL to free resources
    cancelAnimationFrame(introAnimationId);

    // 2. Hide Container completely
    const introContainer = document.getElementById('intro-canvas-container');
    if (introContainer) {
        introContainer.style.display = 'none';
        // Reset opacity if we ever needed to run it again (unlikely here)
        // introContainer.style.opacity = 1; 
    }

}
