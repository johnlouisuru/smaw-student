<?php
// stage_holder.php

// Connect to DB (adjust credentials)
include_once "db-config/security.php";

if (!isLoggedIn()) {
  header('Location: logout/');
  exit;
}


$stageId = $_GET['level'] ?? 1;
$stageLevel = $_GET['level_name'] ?? '--';

$isCongratulate = check_if_allowed_on_next_stage($pdo);
  $total_stages = how_many_levels($pdo);
  if($isCongratulate > $total_stages && $stageId == $isCongratulate){
    header('Location: congrats');
  }

$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE stage_id = ? ORDER BY RAND() LIMIT 1");
$stmt->execute([$stageId]);
$quiz = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die("No quiz found for this stage.");
}


$answer = strtoupper($quiz['correct_answer']);
$answerLength = strlen($answer);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Stage Quiz</title>
<style>
  body {
    margin: 0;
    height: 100vh;
    background: #1a1a1a;
    font-family: Arial, sans-serif;
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    text-align: center;
  }
  .quiz-container { width: 100%; /* full width */ max-width: 600px; /* cap on large screens */ display: flex; flex-direction: column; align-items: stretch; /* children stretch to full width */ padding: 0 15px; /* breathing room on mobile */ } h1, p { width: 100%; text-align: center; }
  p {
    font-size: 50px;
    margin-bottom: 20px;
  }
  /* Slots for underscores */
  #answerSlots { display: flex; justify-content: space-between; /* spread underscores evenly */ flex-wrap: nowrap; width: 100%; margin: 20px 0; } .slot { flex: 1; /* each underscore takes equal space */ text-align: center; font-size: 28px; font-weight: bold; margin: 0 2px; color: #fff; text-shadow: 1px 1px 2px #000; background: linear-gradient(145deg, #d4af37, #a67c00); padding: 8px 0; border-radius: 6px; transition: transform 0.3s ease, box-shadow 0.3s ease; }
  .slot.filled {
    transform: scale(1.2);
    box-shadow: 0 0 12px #ffd700;
  }
  /* Hidden input to capture typing */
  #student_answer { width: 100%; /* full width */ font-size: 22px; padding: 12px; border: 2px solid #ccc; border-radius: 6px; text-align: center; background: #333; color: #fff; margin-bottom: 20px; text-transform: uppercase; }
  #countdownNumber {
    font-size: 30px;
    font-weight: bold;
    margin-bottom: 10px;
    background: linear-gradient(145deg, #ffffffff, #a67c00);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    text-shadow: 1px 1px 3px #ffffffff;
  }
  #progressBar { width: 100%; /* full width */ height: 25px; background: #444; border: 2px solid #999; border-radius: 8px; overflow: hidden; box-shadow: inset 0 0 10px #000; } #progressFill { height: 100%; width: 100%; background: linear-gradient(145deg, #d4af37, #a67c00); transition: width 1s linear; }
  #modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    justify-content: center;
    align-items: center;
  }
  #modalContent {
    background: linear-gradient(145deg, #d4af37, #a67c00);
    padding: 30px;
    border-radius: 12px;
    color: #fff;
    font-size: 22px;
    text-shadow: 1px 1px 2px #000;
    box-shadow: 0 0 20px #000;
  }
  #warningPopup {
    display: none;
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: linear-gradient(145deg, #a67c00, #d4af37);
    color: #fff;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 16px;
    text-shadow: 1px 1px 2px #000;
    box-shadow: 0 0 12px rgba(0,0,0,0.6);
  }

  /* Mobile adjustments */ @media (max-width: 600px) { h1 { font-size: 20px; } p { font-size: 16px; } .slot { font-size: 22px; } #student_answer { font-size: 20px; } }
</style>
</head>
<body>

<div class="quiz-container">
  <h1>Stage <?= htmlspecialchars($stageId) ?> Quiz</h1>
  <p><?= htmlspecialchars($quiz['question']) ?></p>

  <div id="answerSlots">
    <?php for ($i = 0; $i < $answerLength; $i++): ?>
      <span class="slot">_</span>
    <?php endfor; ?>
  </div>
  <input type="text" id="student_answer" maxlength="<?= $answerLength ?>">

  <div id="countdownNumber">10</div>
  <div id="progressBar">
    <div id="progressFill"></div>
  </div>
</div>

<!-- Modal -->
<div id="modal">
  <div id="modalContent">Correct! You are ready to proceed to this stage</div>
</div>

<!-- Warning Popup -->
<div id="warningPopup">That's not the answer</div>

<script>
const correctAnswer = "<?= strtoupper(addslashes($quiz['correct_answer'])) ?>";
const levelId = <?= $stageId ?>;
const levelName = "<?= $stageLevel ?>";
const slots = document.querySelectorAll(".slot");
const input = document.getElementById("student_answer");
const modal = document.getElementById("modal");
const progressFill = document.getElementById("progressFill");
const countdownNumber = document.getElementById("countdownNumber");
const warningPopup = document.getElementById("warningPopup");

window.addEventListener("load", () => { input.focus(); });


// Focus hidden input so keystrokes are captured
document.addEventListener("keydown", () => input.focus());

// Countdown progress bar + number
let timeLeft = 10;
function updateProgress() {
    countdownNumber.textContent = timeLeft;
    const percent = (timeLeft / 10) * 100;
    progressFill.style.width = percent + "%";

    if (timeLeft <= -1) {
        input.disabled = true;
        clearInterval(timer);

        // Show "Uh oh!" modal
        modal.style.display = "flex";
        modal.querySelector("#modalContent").textContent =
          "Uh oh! I'm sorry but we need to go back to the last stage";

        // Redirect after 3 seconds
        setTimeout(() => {
            window.location.href = "app";
        }, 3000);
    }
    timeLeft--;
}
let timer = setInterval(updateProgress, 1000);
updateProgress();

// Continuous answer checking
input.addEventListener("input", () => {
    const val = input.value.toUpperCase();

    // Fill slots with typed characters
    slots.forEach((slot, i) => {
        if (val[i]) {
            slot.textContent = val[i];
            slot.classList.add("filled");
        } else {
            slot.textContent = "_";
            slot.classList.remove("filled");
        }
    });

    // Warning popup if 3+ chars and not correct
    if (val.length >= 3 && val !== correctAnswer) {
        warningPopup.style.display = "block";
        setTimeout(() => {
            warningPopup.style.display = "none";
        }, 2000);
    }

    // Correct answer check
    if (val === correctAnswer) {
        input.disabled = true;
        clearInterval(timer);
        modal.style.display = "flex";
        modal.querySelector("#modalContent").textContent =
          "Correct! You are ready to proceed to this stage";

          setTimeout(() => {
            window.location.href = `check_stage_unlock?current_level=${levelId}`;
        }, 1500);
    }
});
</script>

</body>
</html>
