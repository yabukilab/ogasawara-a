<?php
function login($pdo, $student_number, $password) {
    $sql = "SELECT id, password_hash FROM users WHERE student_number = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$student_number]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['student_number'] = $student_number;
        return true;
    }
    return false;
}
