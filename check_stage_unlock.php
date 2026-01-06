<?php 

include_once "db-config/security.php";

if (!isLoggedIn()) {
  header('Location: logout/');
  exit;
}
  // $_SESSION['student_id'] = 20;
  // $_SESSION['section_id'] = 3;
  // $_SESSION['student_id'] = 20;

  $user_id = $_SESSION['student_id'];
  $currentLevel = $_GET['current_level'];

  if(!isset($_GET['current_level']) || !is_numeric($_GET['current_level'])){
    header('Location: app');
  }

  
  

$validated_next_level = check_if_allowed_on_next_stage($pdo);
  if( $validated_next_level >= $currentLevel ){
    echo "Allowed to level : ".$validated_next_level;
    // $currentLevel = $currentLevel + 1;
    $sql = "
    SELECT level_number, level_name FROM levels WHERE id = :next_level 
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':next_level' => $currentLevel
]);

$levels = $stmt->fetch(PDO::FETCH_ASSOC);

header('Location: welding?level='.$currentLevel.'&level_name='.$levels['level_name']);
  } 
  else {
    echo "SID : $_SESSION[student_id] <br>";
    echo "Not Allowed";
  }