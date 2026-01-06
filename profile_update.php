<?php
include_once "db-config/security.php";

if (!isLoggedIn()) {
  header('Location: logout/');
  exit;
}

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("UPDATE students 
                           SET firstname = :firstname, lastname = :lastname, grade_level = :grade_level 
                           WHERE id = :id");
    $stmt->execute([
        ':firstname' => $_POST['firstname'],
        ':lastname' => $_POST['lastname'],
        ':grade_level' => $_POST['grade_level'],
        ':id' => $_POST['id']
    ]);

    echo json_encode([
        'success' => true,
        'firstname' => $_POST['firstname'],
        'lastname' => $_POST['lastname'],
        'lrn' => $_POST['lrn'],
        'grade_level' => $_POST['grade_level']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
