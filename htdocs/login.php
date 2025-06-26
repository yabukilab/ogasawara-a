<?php
session_start();
require_once 'db_config.php'; // DB 설정 파일 포함

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($student_number) || empty($password)) {
        $message = '<p class="message error">学番とパスワードをすべて入力してください。</p>';
    } else {
        try {
            // db_config.php에서 정의된 상수를 사용합니다.
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", // charset을 utf8mb4로 변경 권장
                DB_USER,
                DB_PASS
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // 1. 사용자 정보를 데이터베이스에서 조회합니다.
            $stmt = $pdo->prepare("SELECT student_number, password, department FROM users WHERE student_number = :student_number");
            $stmt->execute([':student_number' => $student_number]);
            $user = $stmt->fetch(); // 결과 행을 가져옵니다.

            // 2. 조회된 password 컬럼의 해시 값과 입력된 비밀번호를 password_verify()로 비교
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['student_number'] = $user['student_number'];
                // $_SESSION['department'] = $user['department']; // 필요한 경우 학과 정보도 세션에 저장
                header('Location: index.php'); // 로그인 성공 시 메인 페이지로 리다이렉트
                exit;
            } else {
                $message = '<p class="message error">間違った学番またはパスワードです。</p>'; // 오타 수정: 잘못된 -> 間違った
            }
        } catch (PDOException $e) {
            // 데이터베이스 연결 또는 쿼리 오류 발생 시
            error_log("Login DB Error: " . $e->getMessage()); // 서버 로그에 상세 에러 기록
            $message = '<p class="message error">データベースエラーが発生しました。しばらくしてから再度お試しください。</p>';
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
        <?php echo $message; ?>
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