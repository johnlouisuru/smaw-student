<?php
// congrats.php — static display page
include_once "db-config/security.php";
if (!isLoggedIn()) {
  header('Location: logout/');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Congratulations</title>
  <style>
    body {
      margin: 0;
      height: 100vh;
      background: #1a1a1a; /* dark welding theme */
      overflow: hidden;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
      font-family: Arial, sans-serif;
    }

    /* Metallic text */
    h1 {
        text-align: center;
      font-size: 48px;
      font-weight: bold;
      background: linear-gradient(145deg, #d4af37, #a67c00, #d4af37);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 2px 2px 6px #000;
      margin-top: 20px;
      animation: glow 2s ease-in-out infinite alternate;
    }

    @keyframes glow {
      from { text-shadow: 2px 2px 6px #000; }
      to   { text-shadow: 2px 2px 20px #ffcc00; }
    }

    /* Trophy */
    .trophy {
      font-size: 100px;
      color: #FFD700;
      text-shadow: 0 0 20px #ffcc00, 0 0 40px #ffcc00;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%   { transform: scale(1); }
      50%  { transform: scale(1.1); }
      100% { transform: scale(1); }
    }

    /* Confetti canvas */
    canvas {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: -1;
    }

    #homeBtn {
    position: fixed;
    bottom: 20px;          /* lowest part of the screen */
    left: 50%;
    transform: translateX(-50%);
    font-family: Arial, sans-serif;
    font-size: 18px;
    font-weight: bold;
    color: #fff;
    text-decoration: none;
    padding: 12px 24px;
    border-radius: 8px;
    cursor: pointer;

    /* Metallic gradient */
    background: linear-gradient(145deg, #d4af37, #a67c00, #d4af37);
    border: 2px solid #ccc;
    box-shadow: 0 4px 10px rgba(0,0,0,0.6), inset 0 2px 4px rgba(255,255,255,0.3);

    /* Text shadow for depth */
    text-shadow: 1px 1px 2px #000;
    transition: all 0.3s ease;
  }

  #homeBtn:hover {
    background: linear-gradient(145deg, #a67c00, #d4af37, #a67c00);
    box-shadow: 0 6px 14px rgba(0,0,0,0.8), inset 0 2px 6px rgba(255,255,255,0.4);
    transform: translateX(-50%) scale(1.05);
  }

  #homeBtn:active {
    transform: translateX(-50%) scale(0.95);
    box-shadow: 0 2px 6px rgba(0,0,0,0.7) inset;
  }

  .home-icon {
    margin-right: 8px;
  }
  </style>
</head>
<body>
  <!-- Trophy -->
  <div class="trophy">🏆</div>

  <!-- Metallic Text -->
  <h1>Congratulations, you have done all the <?=how_many_levels($pdo); ?> Stages</h1>

  <!-- Confetti Canvas -->
  <canvas id="confetti"></canvas>

  <!-- Home Button --> <a id="homeBtn" href="app"> <span class="home-icon">🏠</span> Home </a>

  <script>
  const canvas = document.getElementById('confetti');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;

const sparks = [];
const colors = ['#ffffff', '#ffd700', '#ff8c00', '#00ffff']; // welding spark colors

function Spark() {
  // Randomize starting position anywhere on screen
  this.x = Math.random() * canvas.width;
  this.y = Math.random() * canvas.height;
  this.size = Math.random() * 2 + 1;
  this.color = colors[Math.floor(Math.random() * colors.length)];
  this.speedX = (Math.random() - 0.5) * 6; // random horizontal direction
  this.speedY = (Math.random() - 0.5) * 6; // random vertical direction
  this.alpha = 1;
  this.decay = 0.01 + Math.random() * 0.02; // fade speed
}

function createSparks() {
  for (let i = 0; i < 20; i++) { // more sparks per batch
    sparks.push(new Spark());
  }
}

function drawSparks() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  sparks.forEach((s, i) => {
    ctx.globalAlpha = s.alpha;
    ctx.fillStyle = s.color;
    ctx.beginPath();
    ctx.arc(s.x, s.y, s.size, 0, Math.PI * 2);
    ctx.fill();

    // movement
    s.x += s.speedX;
    s.y += s.speedY;

    // fade out
    s.alpha -= s.decay;

    // remove faded sparks
    if (s.alpha <= 0) {
      sparks.splice(i, 1);
    }
  });

  ctx.globalAlpha = 1;
  requestAnimationFrame(drawSparks);
}

// continuously generate sparks scattered everywhere
setInterval(createSparks, 150); 
drawSparks();

// Resize handler
window.addEventListener('resize', () => {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
});

</script>

</body>
</html>
