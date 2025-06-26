<?php
session_start(); // 세션은 항상 스크립트 맨 처음에 시작해야 합니다.
require_once 'db_config.php'; // db_config.php 파일을 포함

// HTML 엔티티 변환 함수 (h())가 db_config.php에 없다면 여기에 정의하거나,
// 아래 코드에서 htmlspecialchars()를 직접 사용하세요.
// 이 예시에서는 htmlspecialchars()를 직접 사용합니다.

$error = ''; // 에러 메시지를 저장할 변수 초기화

// 로그인 폼 제출 처리
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 입력값 가져오기 및 보안 처리
    // h() 함수가 정의되어 있다면 h()를 사용하고, 없다면 htmlspecialchars()를 직접 사용합니다.
    $student_number = htmlspecialchars($_POST['student_number'] ?? ''); // 'student_id' 대신 'student_number' 사용
    $password = $_POST['password'] ?? ''; // 비밀번호는 해시 전에 바로 처리

    // 입력값 유효성 검사
    if (empty($student_number) || empty($password)) {
        $error = "学番とパスワードをすべて入力してください。";
    } else {
        try {
            // db_config.php에서 생성된 PDO 객체 변수는 $pdo 입니다.
            // 기존 코드에서는 $db로 사용되었는데, $pdo로 일치시킵니다.
            $stmt = $pdo->prepare("SELECT student_number, password, department FROM users WHERE student_number = :student_number");
            $stmt->bindParam(':student_number', $student_number);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC); // 조회된 사용자 정보

            // 비밀번호 검증
            if ($user && password_verify($password, $user['password'])) {
                // 로그인 성공
                $_SESSION['student_number'] = $user['student_number']; // 세션에 학번 저장
                $_SESSION['department'] = $user['department']; // 세션에 학과 정보 저장 (index.php에서 사용)

                header("Location: index.php"); // 로그인 후 메인 페이지로 이동
                exit();
            } else {
                // 로그인 실패 (학번 또는 비밀번호 불일치)
                $error = "学番またはパスワードが間違っています。";
            }
        } catch (PDOException $e) {
            // 데이터베이스 오류 처리
            error_log("Login DB Error: " . $e->getMessage()); // 서버 로그에 상세 에러 기록
            $error = "データベースエラーが発生しました。しばらくしてから再度お試しください。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン (Login)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1>ログイン</h1>
        <?php if (!empty($error)): ?>
            <p class="message error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form action="login.php" method="post" class="auth-form">
            <label for="student_number">学番:</label>
            <input type="text" id="student_number" name="student_number" required>
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">ログイン</button>
        </form>
        <div class="auth-links">
            <p>アカウントをお持ちでないですか？ <a href="register_user.php">新規ユーザー登録</a></p>
        </div>
    </div>
</body>
</html>