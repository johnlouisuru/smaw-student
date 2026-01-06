
const canvas = document.getElementById('weldingCanvas');
const ctx = canvas.getContext('2d');
const resetBtn = document.getElementById('resetBtn');
const levelSelect = document.getElementById('levelSelect');

levelSelect.value = weldingLevel;
  

const isMobile = window.innerWidth <= 768;
const WELD_TOLERANCE = isMobile ? 14 : 7;

let showTorch = false;

// --- STUDENT INFO ---
const studentId = 123;
// let weldingLevel = 1;

// --- VARIABLES ---
let mouseX = 0, mouseY = 0, isWelding = false;
let weldingTime = 0, weldingInterval = null, bestTime = null;
let sparks = [], weldMarks = [], drips = [], smokeParticles = [], heatHaze = [], glowingBeads = [], errorGlows = [], wrongMarks = [];
let seams = [], doneMessage = false;

let confetti = [];
let showTrophy = false;
let confettiTimer = 0;

function spawnConfetti() {
    confetti = [];

    const spread = isMobile ? 120 : 160;

    for (let i = 0; i < 140; i++) {
        confetti.push({
            x: canvas.width / 2 + (Math.random() - 0.5) * spread,
            y: 70,
            vx: (Math.random() - 0.5) * 2,    // slower horizontal drift
            vy: Math.random() * -2 - 1,       // gentle upward push
            size: 4 + Math.random() * 4,
            rotation: Math.random() * Math.PI,
            rotationSpeed: (Math.random() - 0.5) * 0.1,
            color: `hsl(${Math.random() * 360}, 80%, 60%)`,
            life: 220                          // longer life
        });
    }

    showTrophy = true;

    if (navigator.vibrate) {
        navigator.vibrate([60, 120, 60]);
    }
}



// --- BACKGROUND ---
let bg = new Image();
function pickRandomBackground() {
    const rand = Math.floor(Math.random() * 10) + 1; // 1–10
    bg.src = `assets/img/${rand}.jpg`;
}
pickRandomBackground();

window.addEventListener("orientationchange", () => {
  setTimeout(() => {
    resize();
    initSeams(weldingLevel);
  }, 300);
});

// --- CANVAS ---
function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; }
window.addEventListener('resize', resize);
resize();

// --- MOUSE EVENTS ---
window.addEventListener('mousemove', (e) => { mouseX = e.clientX; mouseY = e.clientY; });
window.addEventListener('mousedown', () => { startWelding(); });
window.addEventListener('mouseup', () => { stopWelding(); });

// --- TOUCH EVENTS ---
function getTouchPos(e){
    const rect = canvas.getBoundingClientRect();
    const touch = e.touches[0];
    return { x: touch.clientX - rect.left, y: touch.clientY - rect.top };
}

function vibrate(pattern = 30) {
    if (navigator.vibrate) {
        navigator.vibrate(pattern);
    }
}


canvas.addEventListener('touchstart', (e)=>{
    e.preventDefault();
    const pos = getTouchPos(e);
    mouseX = pos.x;
    mouseY = pos.y;
    showTorch = true;
    startWelding();
});

canvas.addEventListener('touchmove', (e)=>{
    e.preventDefault();
    const pos = getTouchPos(e);
    mouseX = pos.x;
    mouseY = pos.y;
});

canvas.addEventListener('touchend', (e)=>{
    e.preventDefault();
    showTorch = false;
    stopWelding();
});


// --- START/STOP WELDING ---
function startWelding() {
    if (!doneMessage) { 
        isWelding = true; 
        if (!weldingInterval) {
            weldingInterval = setInterval(() => {
                weldingTime++;

                // Update the <span> every tick
                const timerSpan = document.getElementById("timer_holder");
                if (timerSpan) {
                    timerSpan.textContent = `Time: ${weldingTime}s`;
                }

                // Optional: update best time span too
                const bestSpan = document.getElementById("besttime_holder");
                if (bestSpan && bestTime !== null) {
                    bestSpan.textContent = `Best Time: ${bestTime}s`;
                }
            }, 1000);
        }
    }
}

function stopWelding(){ isWelding = false; }

// --- SEND RESULT TO SERVER ---
function sendWeldingResult() {
    const data = `student_id=${studentId}&time_used=${weldingTime}&welding_level=${weldingLevel}`;
    fetch('welding_result.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(res => res.json())
    .then(response => {
        console.log('Result saved:', response);
        console.log(`Your welding result has been saved! Done in: ${weldingTime} seconds. `);   
        Swal.fire({
        title: "Good job! 🏆",
        text: "Your welding result has been saved! Done in: "+weldingTime+" seconds. 🎉🎉🎉",
        icon: "success",
        background: 'rgba(255,255,255,0.85)' // white with 50% opacity
        });
    })
    .catch(err => {
        console.error('Failed to save result:', err);
    });
}

// --- CREATE SEAMS ---
function initSeams(level){
    const cx = canvas.width/2, cy = canvas.height/2;
    const size = 200, segments = 50;
    seams = [];

    function createVertical(x,y,h){ const pts=[]; for(let i=0;i<=segments;i++) pts.push({x:x, y:y+i*(h/segments), welded:false}); return pts; }
    function createHorizontal(x,y,w){ const pts=[]; for(let i=0;i<=segments;i++) pts.push({x:x+i*(w/segments), y:y, welded:false}); return pts; }
    function createDiagonal(x1,y1,x2,y2){ const pts=[]; for(let i=0;i<=segments;i++){ const t=i/segments; pts.push({x:x1+t*(x2-x1), y:y1+t*(y2-y1), welded:false}); } return pts; }
    function createCurve(cx,cy,r,start,end){ const pts=[]; for(let i=0;i<=segments;i++){ const angle=start+(end-start)*(i/segments); pts.push({x:cx+Math.cos(angle)*r, y:cy+Math.sin(angle)*r, welded:false}); } return pts; }

    switch(level){
        case 1: seams.push({points:createVertical(cx-10,cy-size/2,size)}); break;
        case 2: seams.push({points:createVertical(cx-10,cy-size/2,size)}); seams.push({points:createHorizontal(cx-size/2,cy-10,size)}); break;
        case 3: seams.push({points:createDiagonal(cx-size/2,cy-size/2,cx+size/2,cy+size/2)}); seams.push({points:createDiagonal(cx+size/2,cy-size/2,cx-size/2,cy+size/2)}); break;
        case 4: [-50,0,50].forEach(off=>seams.push({points:createVertical(cx+off-10,cy-size/2,size)})); break;
        case 5: seams.push({points:createCurve(cx,cy,size/2,0,Math.PI)}); break;
        case 6: seams.push({points:createVertical(cx-30,cy-size/2,size)}); seams.push({points:createHorizontal(cx-50,cy-10,size)}); break;
        case 7: const pts=[]; const zig=6; for(let i=0;i<=segments;i++){ const t=i/segments; const y=cy-size/2+t*size; const x=cx+Math.sin(t*zig*Math.PI)*(size/4); pts.push({x:x,y:y,welded:false}); } seams.push({points:pts}); break;
        case 8: seams.push({points:createVertical(cx-10,cy-size/2,size)}); seams.push({points:createHorizontal(cx-size/2,cy-10,size)}); seams.push({points:createDiagonal(cx-size/2,cy-size/2,cx+size/2,cy+size/2)}); seams.push({points:createDiagonal(cx+size/2,cy-size/2,cx-10,cy+size/2)}); break;
    }
}
initSeams(weldingLevel);

// --- LEVEL CHANGE ---
levelSelect.addEventListener('change', e => {
    weldingLevel = parseInt(e.target.value);
    resetWelding();

    // Optional: update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('level', weldingLevel);
    window.history.replaceState({}, '', url);
});

// --- RESET ---
function resetWelding(){
    doneMessage=false; weldingTime=0; clearInterval(weldingInterval); weldingInterval=null;
    weldMarks.length=0; drips.length=0; smokeParticles.length=0; heatHaze.length=0; glowingBeads.length=0;
    errorGlows.length=0; wrongMarks.length=0;
    pickRandomBackground();
    initSeams(weldingLevel);
}
resetBtn.addEventListener('click', resetWelding);

// --- SPAWN EFFECTS ---
function spawnEffects(x,y){
    if(Math.random()<0.2) sparks.push({x:x,y:y,radius:1+Math.random()*2,life:10+Math.random()*10});
    if(Math.random()<0.15) smokeParticles.push({x:x,y:y-5,radius:2+Math.random()*3,speedY:0.5+Math.random(),opacity:0.3+Math.random()*0.3});
    if(Math.random()<0.2) heatHaze.push({x:x,y:y-5,speedY:0.3+Math.random()*0.3,opacity:0.2+Math.random()*0.2,offset:Math.random()*10});
    if(Math.random()<0.1) drips.push({x:x,y:y,radius:2+Math.random()*2,speed:1+Math.random(),opacity:1});
    glowingBeads.push({x:x,y:y,radius:2+Math.random()*2,glow:10+Math.random()*10,opacity:1});
}

// --- CHECK WELD ---
function checkWeld(x,y){
    let allDone=true, weldedThisFrame=false, nearAnyPoint=false;

    seams.forEach(seam=>{
        seam.points.forEach(p=>{
            const dx=x-p.x, dy=y-p.y, dist=Math.sqrt(dx*dx+dy*dy);

            if (dist < WELD_TOLERANCE) { // hit range
                nearAnyPoint=true;
                if (!p.welded) {
                    p.welded = true;
                    weldMarks.push({ x: p.x, y: p.y, radius: 10 });
                    spawnEffects(p.x, p.y);
                    weldedThisFrame = true;

                    // 📳 short vibration on correct weld
                    vibrate(20);
                }

            }
        });
        if(!seam.points.every(p=>p.welded)) allDone=false;
    });

    // show red error glow only when not near seam
    if(!weldedThisFrame && isWelding && !nearAnyPoint){
        
        errorGlows.push({
            x: x,
            y: y - (isMobile ? 6 : 0),
            radius: isMobile ? 2 : 1,
            life: 10
        });
        wrongMarks.push({x:x,y:y,radius:2});
        vibrate([10, 20, 10]);
    }

    if (!doneMessage && allDone) {
        doneMessage = true;

        if (weldingInterval) {
            clearInterval(weldingInterval);
            weldingInterval = null;
        }

        spawnConfetti();   // 🎉🎉🎉
        sendWeldingResult();
    }

}

// --- DRAW LOOP ---
function draw(){

    // 🏆 Trophy
        if (showTrophy) {
            drawTrophy(canvas.width / 2, 95, isMobile ? 1.2 : 1.5);
        }


    ctx.clearRect(0,0,canvas.width,canvas.height);

    // Background plate
    ctx.drawImage(bg, 0, 0, canvas.width, canvas.height);

    // Seams
    seams.forEach(seam=>{
    const pts = seam.points;
    if (pts.length > 1) {
        ctx.save();
        ctx.strokeStyle = "black";   // dark groove
        ctx.lineWidth = isMobile ? 20 : 14;          // 👈 make seam thicker
        ctx.lineCap = "round";
        ctx.beginPath();
        ctx.moveTo(pts[0].x, pts[0].y);
        for (let i = 1; i < pts.length; i++) {
            ctx.lineTo(pts[i].x, pts[i].y);
        }
        ctx.stroke();
        ctx.restore();
    }

    // 🔥 Virtual Welding Torch (Mobile)
    if (showTorch) {
        ctx.save();

        // Outer glow
        ctx.shadowBlur = 25;
        ctx.shadowColor = "rgba(255,150,50,0.9)";
        ctx.beginPath();
        ctx.arc(mouseX, mouseY, 8, 0, Math.PI * 2);
        ctx.fillStyle = "orange";
        ctx.fill();

        // Hot core
        ctx.shadowBlur = 10;
        ctx.beginPath();
        ctx.arc(mouseX, mouseY, 4, 0, Math.PI * 2);
        ctx.fillStyle = "white";
        ctx.fill();

        ctx.restore();
    }

});

    // Welded marks with shiny filler
    weldMarks.forEach(m=>{
        ctx.save();
        ctx.shadowBlur=10;
        ctx.shadowColor='rgba(255,200,80,0.8)';
        ctx.beginPath();
        ctx.arc(m.x,m.y,m.radius,0,Math.PI*2);
        const grad=ctx.createRadialGradient(m.x,m.y,0,m.x,m.y,m.radius);
        grad.addColorStop(0,'#fff');
        grad.addColorStop(0.5,'#f90');
        grad.addColorStop(1,'#c60');
        ctx.fillStyle=grad;
        ctx.fill();
        ctx.restore();
    });

    // Wrong marks
    wrongMarks.forEach(m=>{
        ctx.beginPath();
        ctx.arc(m.x,m.y,m.radius,0,Math.PI*2);
        ctx.fillStyle='red';
        ctx.fill();
    });

    if(isWelding) checkWeld(mouseX,mouseY);

    // Sparks
    for(let i=sparks.length-1;i>=0;i--){
        const s=sparks[i];
        ctx.beginPath(); ctx.arc(s.x,s.y,s.radius,0,Math.PI*2);
        ctx.fillStyle=`rgba(255,255,0,${s.life/20})`;
        ctx.fill(); s.x+=Math.random()*2-1; s.y+=Math.random()*2-1; s.life-=0.5;
        if(s.life<=0) sparks.splice(i,1);
    }

    // Error Glows
    for(let i=errorGlows.length-1;i>=0;i--){
        const e=errorGlows[i];
        ctx.beginPath();
        ctx.arc(e.x,e.y,e.radius,0,Math.PI*2);
        ctx.fillStyle=`rgba(255,0,0,${e.life/10})`;
        ctx.fill();
        e.radius+=0.2; e.life-=1;
        if(e.life<=0) errorGlows.splice(i,1);
    }

    // Drips
    for(let i=drips.length-1;i>=0;i--){
        const d=drips[i];
        ctx.beginPath(); ctx.arc(d.x,d.y,d.radius,0,Math.PI*2);
        ctx.fillStyle=`rgba(255,140,0,${d.opacity})`;
        ctx.fill();
        d.y+=d.speed; d.opacity-=0.01;
        if(d.opacity<=0||d.y>canvas.height) drips.splice(i,1);
    }

    // Smoke
    for(let i=smokeParticles.length-1;i>=0;i--){
        const s=smokeParticles[i];
        ctx.beginPath(); ctx.arc(s.x,s.y,s.radius,0,Math.PI*2);
        ctx.fillStyle=`rgba(150,150,150,${s.opacity})`;
        ctx.fill();
        s.y-=s.speedY; s.x+=Math.random()*0.5-0.25;
        s.radius+=0.02; s.opacity-=0.005;
        if(s.opacity<=0) smokeParticles.splice(i,1);
    }

    // Heat Haze
    for(let i=heatHaze.length-1;i>=0;i--){
        const h=heatHaze[i];
        const wave=Math.sin(h.offset+Date.now()*0.005)*2;
        ctx.beginPath();
        ctx.moveTo(h.x+wave,h.y);
        ctx.bezierCurveTo(h.x+wave+5,h.y-10,h.x+wave-5,h.y-20,h.x+wave,h.y-30);
        ctx.strokeStyle=`rgba(255,255,255,${h.opacity})`;
        ctx.lineWidth=1; ctx.stroke();
        h.y-=h.speedY; h.offset+=0.05; h.opacity-=0.003;
        if(h.opacity<=0) heatHaze.splice(i,1);
    }

    // Glowing Beads
    for(let i=glowingBeads.length-1;i>=0;i--){
        const b=glowingBeads[i];
        ctx.save();
        ctx.shadowBlur=b.glow; ctx.shadowColor='orange';
        ctx.beginPath(); ctx.arc(b.x,b.y,b.radius,0,Math.PI*2);
        ctx.fillStyle=`rgba(255,165,0,${b.opacity})`;
        ctx.fill(); ctx.restore();
        b.opacity-=0.02; b.glow-=0.5;
        if(b.opacity<=0) glowingBeads.splice(i,1);
    }

    // Done message
    if (doneMessage) {
        drawTrophy(canvas.width / 2, canvas.height / 2 - 100);

        const pulse = 0.5 + 0.5 * Math.sin(Date.now() * 0.005);

        ctx.save();
        ctx.font = isMobile ? 'bold 36px Arial' : 'bold 48px Arial';
        ctx.fillStyle = 'white';              // fixed white text
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        // Shadow with pulsing opacity
        ctx.shadowColor = `rgba(0,0,0,${pulse})`; // black shadow, alpha pulsing
        ctx.shadowBlur = 8;
        ctx.shadowOffsetX = 2;
        ctx.shadowOffsetY = 2;

        ctx.fillText('Good Job!', canvas.width / 2, canvas.height / 2);

        ctx.restore();
    }




    // Timer
    ctx.save();
    ctx.font='24px Arial'; ctx.fillStyle='white';
    ctx.fillText(`Time: ${weldingTime}s`,20,40);
    if(bestTime!==null) ctx.fillText(`Best Time: ${bestTime}s`,20,70);
    ctx.restore();

    requestAnimationFrame(draw);

    // 🎊 Confetti
        // 🎊 Confetti (slow + scattered)
        for (let i = confetti.length - 1; i >= 0; i--) {
            const c = confetti[i];

            ctx.save();
            ctx.translate(c.x, c.y);
            ctx.rotate(c.rotation);

            ctx.fillStyle = c.color;
            ctx.fillRect(-c.size / 2, -c.size / 2, c.size, c.size);

            ctx.restore();

            c.x += c.vx;
            c.y += c.vy;
            c.vy += 0.08;          // slow gravity
            c.rotation += c.rotationSpeed;
            c.life--;

            if (c.life <= 0 || c.y > canvas.height + 40) {
                confetti.splice(i, 1);
            }
        }


}

function drawTrophy(x, y) {
    ctx.save();

    // Pulse factor (oscillates between 0 and 1)
    const pulse = 0.5 + 0.5 * Math.sin(Date.now() * 0.005);

    // Glow with pulsing opacity
    ctx.shadowBlur = 25;
    ctx.shadowColor = `rgba(255,215,0,${pulse})`; // gold glow that pulses

    // Trophy colors (you can also pulse the fill if desired)
    ctx.fillStyle = "#FFD700";   // gold body
    ctx.strokeStyle = "#B8860B"; // darker outline
    ctx.lineWidth = 3;

    // Cup body
    ctx.beginPath();
    ctx.moveTo(x - 30, y - 30);
    ctx.lineTo(x + 30, y - 30);
    ctx.lineTo(x + 22, y);
    ctx.lineTo(x - 22, y);
    ctx.closePath();
    ctx.fill();
    ctx.stroke();

    // Left handle
    ctx.beginPath();
    ctx.arc(x - 35, y - 15, 12, Math.PI / 2, Math.PI * 1.5);
    ctx.stroke();

    // Right handle
    ctx.beginPath();
    ctx.arc(x + 35, y - 15, 12, Math.PI * 1.5, Math.PI / 2);
    ctx.stroke();

    // Stem
    ctx.fillRect(x - 8, y, 16, 15);

    // Base
    ctx.fillRect(x - 28, y + 15, 56, 10);

    ctx.restore();
}



draw();