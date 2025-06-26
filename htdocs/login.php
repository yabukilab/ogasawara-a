<?php
require_once 'db_config.php'; // db_config.php 파일을 포함 (이 줄이 login.php에도 있어야 함)

// ... (이전의 HTML 이스케이프 함수 h() 등 필요하다면 db_config.php에서 불러오도록 할 수 있음)

// 로그인 폼 제출 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = h($_POST['student_id']); // h() 함수가 db_config.php에 정의되어 있으면 사용 가능
    $password = $_POST['password']; // 비밀번호는 해시 전에 바로 처리

    try {
        // $db는 db_config.php에서 이미 생성된 PDO 객체입니다.
        $stmt = $db->prepare("SELECT password FROM users WHERE student_id = :student_id");
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // 로그인 성공
            session_start();
            $_SESSION['student_id'] = $student_id;
            header("Location: index.php"); // 로그인 후 이동할 페이지
            exit();
        } else {
            // 로그인 실패
            $error = "学籍番号またはパスワードが間違っています。";
        }
    } catch (PDOException $e) {
        // 데이터베이스 오류 처리
        error_log("Login DB Error: " . $e->getMessage());
        $error = "データベースエラーが発生しました。しばらくしてから再度お試しください。";
    }
}
?>