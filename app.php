<?php
include_once "db-config/security.php";

// If already logged in and profile complete, redirect to dashboard
if (!isLoggedIn()) {
  header('Location: logout/');
  exit;
}

// $_SESSION['student_id'] = 20;
// $_SESSION['section_id'] = 3;
// $_SESSION['student_id'] = 20;

$user_id = $_SESSION['student_id'];

$sql = "
    SELECT 
        l.id,
        l.level_number,
        l.level_name,
        l.stage_id,
        l.created_at,
        MAX(sr.welding_level) AS welding_level
    FROM levels l
    LEFT JOIN student_result sr
        ON sr.student_id = :student_id
        AND sr.welding_level = l.level_number
    GROUP BY 
        l.id,
        l.level_number,
        l.level_name,
        l.stage_id,
        l.created_at
    ORDER BY l.level_number ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':student_id' => $user_id
]);

$levels = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch total levels 
$totalLevelsStmt = $pdo->query("SELECT COUNT(id) AS total_levels FROM levels");
$totalLevels = $totalLevelsStmt->fetch(PDO::FETCH_ASSOC)['total_levels'];
// Fetch unlocked stages for this student 
$unlockedStmt = $pdo->prepare(" SELECT DISTINCT welding_level FROM student_result WHERE student_id = :uid ORDER BY welding_level ASC ");
$unlockedStmt->execute([':uid' => $user_id]);
$unlockedStages = $unlockedStmt->fetchAll(PDO::FETCH_COLUMN);
// Calculate completion % 
$unlockedCount = count($unlockedStages);
$completionPercent = $totalLevels > 0 ? round(($unlockedCount / $totalLevels) * 100) : 0;
// Fetch all attempts sorted by shortest time 
$attemptsStmt = $pdo->prepare(" SELECT welding_level, time_used, date_created FROM student_result WHERE student_id = :uid ORDER BY time_used ASC ");
$attemptsStmt->execute([':uid' => $user_id]);
$attempts = $attemptsStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch student profile 
$stmt = $pdo->prepare("SELECT id, lastname, firstname, lrn, grade_level, section_id FROM students WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <!-- <title>Game Roadmap</title> -->
  <link rel="apple-touch-icon" sizes="76x76" href="<?= $_ENV['PAGE_ICON'] ?>">
  <link rel="icon" type="image/png" href="<?= $_ENV['PAGE_ICON'] ?>">
  <title><?= $_ENV['PAGE_HEADER'] ?></title>
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Font Awesome 6 -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet" />


  <link href="css/app.css" rel="stylesheet" />

  <style>
    #logoutBtn {
      display: inline-block;
      font-family: Arial, sans-serif;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
      text-decoration: none;
      padding: 12px 28px;
      border-radius: 8px;
      cursor: pointer;

      /* Metallic gradient */
      background: linear-gradient(145deg, #d4af37, #a67c00, #d4af37);
      border: 2px solid #ccc;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.6), inset 0 2px 4px rgba(255, 255, 255, 0.3);

      /* Text shadow for depth */
      text-shadow: 1px 1px 2px #000;
      transition: all 0.3s ease;
    }

    #logoutBtn:hover {
      background: linear-gradient(145deg, #a67c00, #d4af37, #a67c00);
      box-shadow: 0 6px 14px rgba(0, 0, 0, 0.8), inset 0 2px 6px rgba(255, 255, 255, 0.4);
      transform: scale(1.05);
    }

    #logoutBtn:active {
      transform: scale(0.95);
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.7) inset;
    }
  </style>
</head>

<body>

  <div class="container mt-4">

    <!-- Stages Page -->
    <?php
    $sqlMax = "
          SELECT MAX(welding_level) 
          FROM student_result 
          WHERE student_id = :student_id
      ";
    $stmtMax = $pdo->prepare($sqlMax);
    $stmtMax->execute(['student_id' => $user_id]);

    $maxCompletedLevel = (int)$stmtMax->fetchColumn();
    ?>
    <div id="page-stages" role="region" aria-label="Stages page">
      <h3 class="text-center mb-4">Your SMAW Roadmap</h3>

      <div class="row g-3">
        <?php foreach ($levels as $row): ?>

          <?php
          $currentLevel = (int)$row['level_number'];

          // Unlock completed + next stage
          $isUnlocked = ($currentLevel === 1)
            || ($currentLevel <= ($maxCompletedLevel + 1));

          $badgeText  = $isUnlocked ? 'Unlocked' : 'Locked';
          $badgeClass = $isUnlocked ? 'bg-success' : 'bg-danger';
          $cardClass  = $isUnlocked ? '' : 'locked';
          $modalId    = 'stage' . $currentLevel . 'Modal';
          ?>

          <div class="col-6">
            <div class="card stage-card <?= $cardClass ?>"
              <?php if ($isUnlocked): ?>
              data-bs-toggle="modal"
              data-bs-target="#<?= $modalId ?>"
              <?php else: ?>
              data-bs-toggle="modal"
              data-bs-target="#lockedStageModal"
              <?php endif; ?>>
              <div class="card-body text-center py-4">
                <h5 class="mb-1">Stage <?= $currentLevel ?></h5>
                <span class="badge <?= $badgeClass ?>">
                  <?= $badgeText ?>
                </span>
              </div>
            </div>
          </div>

        <?php endforeach; ?>

        <div class="col-12">
          <div class="card stage-card locked"
            data-bs-toggle="modal"
            data-bs-target="#final_boss"
            data-bs-toggle="modal"
            data-bs-target="#lockedStageModal">
            <div class="card-body text-center py-4">
              <h5 class="mb-1">Final Stage</h5>
              <span class="badge bg-warning">
                Final Boss!
              </span>
            </div>
          </div>
        </div>
      </div>
      <hr />

      <?php foreach ($levels as $row): ?>

        <?php
        $currentLevel = (int)$row['level_number'];

        // SAME unlock logic as cards
        $isUnlocked = ($currentLevel === 1)
          || ($currentLevel <= ($maxCompletedLevel + 1));

        if (!$isUnlocked) continue;

        $nextLevel = $currentLevel + 1;
        $modalId   = 'stage' . $currentLevel . 'Modal';
        ?>

        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1"
          aria-hidden="true"
          aria-labelledby="<?= $modalId ?>Label">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="<?= $modalId ?>Label">
                  Stage <?= $currentLevel ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                Welcome to <strong>Stage <?= $currentLevel ?></strong>.<br><br>
                This challenge is called
                <strong><?= htmlspecialchars($row['level_name']) ?></strong>.<br><br>
                Complete it to unlock <strong>Stage <?= $nextLevel ?></strong>.
              </div>

              <div class="modal-footer">
                <a href="stage_holder?level=<?= $currentLevel ?>&level_name=<?= urlencode($row['level_name']) ?>"
                  class="btn btn-success">
                  Start
                </a>
              </div>

            </div>
          </div>
        </div>

        <div class="modal fade" id="final_boss" tabindex="-1"
          aria-hidden="true"
          aria-labelledby="final_bossLabel">
          <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

              <div class="modal-header">
                <h5 class="modal-title" id="final_bossLabel">
                  Final Stage!
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>

              <div class="modal-body">
                <h5 class="text text-info">
                  Final Stage is about to arrive! <br>Are you Prepared? <br>Weld is on its way...
                </h5>
              </div>

              <div class="modal-footer">
                <button class="btn btn-success" data-bs-dismiss="modal">Ok!
                </button>
              </div>

            </div>
          </div>
        </div>

      <?php endforeach; ?>



    </div>


    <!-- Progress Page -->
    <div id="page-progress" role="region" aria-label="Progress page">
      <h3 class="text-center mb-4">Progress</h3> <!-- Overall Completion -->
      <div class="card mb-4">
        <div class="card-body">
          <p class="mb-2">Overall completion</p>
          <div class="progress" style="height: 30px;">
            <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $completionPercent ?>%;" aria-valuenow="<?= $completionPercent ?>" aria-valuemin="0" aria-valuemax="100"> <?= $completionPercent ?>% Complete </div>
          </div>
          <div class="mt-3 d-flex justify-content-between"> <span>Stages unlocked</span> <span class="fw-bold text-warning"><?= $unlockedCount ?> / <?= $totalLevels ?></span> </div>
        </div>
      </div> <!-- Unlocked Stages -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="mb-3">Unlocked Stages</h5>
          <ul class="list-group"> <?php foreach ($unlockedStages as $stage): ?> <li class="list-group-item"> Stage <?= htmlspecialchars($stage) ?> </li> <?php endforeach; ?> </ul>
        </div>
      </div> <!-- Attempts Table -->
      <div class="card">
        <div class="card-body">
          <h5 class="mb-3">Attempts (sorted by shortest time)</h5>
          <table class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Stage</th>
                <th>Time Used (seconds)</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody> <?php foreach ($attempts as $attempt): ?> <tr>
                  <td>Stage <?= htmlspecialchars($attempt['welding_level']) ?></td>
                  <td><?= htmlspecialchars($attempt['time_used']) ?></td>
                  <td><?= htmlspecialchars($attempt['date_created']) ?></td>
                </tr> <?php endforeach; ?> </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Profile Page -->
    <div id="page-profile" role="region" aria-label="Profile page"> <h3 class="text-center mb-4">Profile</h3> 
      <div class="card shadow-lg border-0"> <div class="card-body text-center"> 
        <img src="<?= $_ENV['PAGE_ICON'] ?>" class="rounded-circle mb-3 img-thumbnail border border-warning" width="150" height="150" alt="Profile" /> 
        <h5 id="profileName" class="mb-1 text-warning fw-bold"> 
            <?= htmlspecialchars($student['firstname'].' '.$student['lastname']) ?> 
        </h5> <p class="mb-0">LRN: <span id="profileLRN" class="fw-bold"><?= htmlspecialchars($student['lrn']) ?>
      </span></p> 
      <p class="mb-0">Grade Level: 
        <span id="profileGrade" class="fw-bold"><?= htmlspecialchars($student['grade_level']) ?>
      </span></p> 
      <p class="mb-0">Section Name / Teacher: 
        <span id="sectionName" class="fw-bold">
          <?php 
            $section_name_and_teacher = get_section_name($pdo, $student['section_id']);
            
          ?>
          <?= $section_name_and_teacher['section_name'] ?>
          [<?= $section_name_and_teacher['teacher_fullname'] ?>]
      </span></p> 
      <hr />
      <p class="text text-warning small mt-2">Keep progressing to unlock more stages and rewards.</p> <!-- Edit Button --> 
      <button class="btn btn-warning mt-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editProfileModal"> 
        ✏️ Edit Profile </button> 
      </div> 
    </div> 
  </div>
    <div class="text-center mt-4"> <a href="logout/index.php" id="logoutBtn">🚪 Logout</a> </div>
    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border border-warning">
          <div class="modal-header bg-dark text-warning">
            <h5 class="modal-title" id="editProfileLabel">Edit Profile</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form id="editProfileForm" method="POST" action="profile_update.php">
            <div class="modal-body">
              <input type="hidden" name="id" value="<?= $student['id'] ?>">
              <div class="mb-3">
                <label class="form-label">First Name</label>
                <input type="text" name="firstname" class="form-control" value="<?= htmlspecialchars($student['firstname']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Last Name</label>
                <input type="text" name="lastname" class="form-control" value="<?= htmlspecialchars($student['lastname']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="form-label">LRN</label>
                <?= htmlspecialchars($student['lrn']) ?>
              </div>
              <input type="text" name="lrn" class="form-control" value="<?= htmlspecialchars($student['lrn']) ?>" hidden>
              <div class="mb-3">
                <label class="form-label">Grade Level</label>
                <!-- <input type="text" name="grade_level" class="form-control" value="<?= htmlspecialchars($student['grade_level']) ?>" required> -->
                <?php // Fetch grade levels 
                $stmt = $pdo->query("SELECT id, grade_level FROM grade_level ORDER BY grade_level ASC"); 
                $grades = $stmt->fetchAll(PDO::FETCH_ASSOC); ?> 
                <select name="grade_level" class="form-select"> <?php foreach ($grades as $g): ?> 
                  <option value="<?= $g['grade_level'] ?>" <?= ($student['grade_level'] == $g['grade_level']) ? 'selected' : '' ?>> 
                    <?= htmlspecialchars($g['grade_level']) ?> </option> <?php endforeach; ?> 
                </select>
              </div>
            </div>
            <div class="modal-footer bg-dark">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-warning fw-bold">Save Changes</button>
            </div>
          </form>
        </div>
      </div>
    </div>


  </div>


  <!-- Stage Modal Reusable -->
  <!-- <div class="modal fade" id="stage1Modal" tabindex="-1" aria-hidden="true" aria-labelledby="stage1Label">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 id="stage1Label" class="modal-title">Stage 1</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Welcome to Stage {levels.level_number} This is challenge is called {levels.level_name}. Complete it to unlock Stage {levels.level_number +1 }.
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success" data-bs-dismiss="modal">Start</button>
        </div>
      </div>
    </div>
  </div> -->


  <!-- Bottom Navigation -->
  <nav class="bottom-nav" aria-label="Bottom navigation">
    <button onclick="showPage('stages')" id="btn-stages" class="active" aria-label="Go to Stages">
      <i class="fa-solid fa-gamepad"></i>
      Stages
    </button>
    <button onclick="showPage('progress')" id="btn-progress" aria-label="Go to Progress">
      <i class="fa-solid fa-chart-line"></i>
      Progress
    </button>
    <button onclick="showPage('profile')" id="btn-profile" aria-label="Go to Profile">
      <i class="fa-solid fa-user"></i>
      Profile
    </button>
  </nav>

  <div class="modal fade" id="lockedStageModal" tabindex="-1"
    aria-hidden="true"
    aria-labelledby="lockedStageLabel">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content border-danger">

        <div class="modal-header bg-danger text-white">
          <h5 class="modal-title" id="lockedStageLabel">
            Stage Locked
          </h5>
          <button type="button" class="btn-close btn-close-white"
            data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
          <i class="fa-solid fa-lock fa-3x text-danger mb-3"></i>
          <p class="fw-bold mb-1">This Stage is LOCKED</p>
          <p class="text-muted mb-0">
            Complete the previous stage to unlock it.
          </p>
        </div>

        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-outline-danger"
            data-bs-dismiss="modal">
            OK
          </button>
        </div>

      </div>
    </div>
  </div>

  <!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1055">
  <div id="profileToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div id="toastMessage" class="toast-body">Profile updated successfully!</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    function showPage(page) {
      // Toggle pages
      document.getElementById("page-stages").classList.add("d-none");
      document.getElementById("page-progress").classList.add("d-none");
      document.getElementById("page-profile").classList.add("d-none");
      document.getElementById("page-" + page).classList.remove("d-none");

      // Update active button state
      document.getElementById("btn-stages").classList.remove("active");
      document.getElementById("btn-progress").classList.remove("active");
      document.getElementById("btn-profile").classList.remove("active");

      document.getElementById("btn-" + page).classList.add("active");
    }
  </script>

  <script>
document.getElementById('editProfileForm').addEventListener('submit', function(e) {
  e.preventDefault(); // stop normal form submit

  const formData = new FormData(this);

  fetch(this.action, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    const toastEl = document.getElementById('profileToast');
    const toastMessage = document.getElementById('toastMessage');
    const toast = new bootstrap.Toast(toastEl);

    if (data.success) {
      // Update profile card text
      document.getElementById('profileName').textContent = data.firstname + ' ' + data.lastname;
      document.getElementById('profileLRN').textContent = data.lrn;
      document.getElementById('profileGrade').textContent = data.grade_level;

      // Success toast
      toastEl.classList.remove('text-bg-danger');
      toastEl.classList.add('text-bg-success');
      toastMessage.textContent = "Profile updated successfully!";
      
      // Close modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('editProfileModal'));
      modal.hide();
    } else {
      // Error toast
      toastEl.classList.remove('text-bg-success');
      toastEl.classList.add('text-bg-danger');
      toastMessage.textContent = data.error || "Update failed.";
    }

    toast.show();
  })
  .catch(err => {
    const toastEl = document.getElementById('profileToast');
    const toastMessage = document.getElementById('toastMessage');
    const toast = new bootstrap.Toast(toastEl);

    toastEl.classList.remove('text-bg-success');
    toastEl.classList.add('text-bg-danger');
    toastMessage.textContent = "Server error: " + err.message;
    toast.show();
  });
});
</script>

</body>

</html>