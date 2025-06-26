<?php
session_start();
require_once 'db_config.php'; // DB 설정 파일 포함

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $department = $_POST['department'] ?? ''; // department 값 추가
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($student_number) || empty($department) || empty($password) || empty($confirm_password)) {
        $message = '<p class="message error">모든 필드를 입력해주세요。</p>';
    } elseif ($password !== $confirm_password) {
        $message = '<p class="message error">비밀번호가 일치하지 않습니다。</p>';
    } elseif (strlen($password) < 6) { // 최소 비밀번호 길이 설정
        $message = '<p class="message error">비밀번호는 6자 이상이어야 합니다。</p>';
    } else {
        // 학번 중복 확인
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_number = :student_number");
        $stmt->execute([':student_number' => $student_number]);
        if ($stmt->fetchColumn() > 0) {
            $message = '<p class="message error">이미 존재하는 학번입니다. 다른 학번을 사용해주세요。</p>';
        } else {
            // 비밀번호를 해싱하여 'password' 컬럼에 저장
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                // 'department' 컬럼을 포함하여 데이터 삽입
                $stmt = $pdo->prepare("INSERT INTO users (student_number, department, password) VALUES (:student_number, :department, :password)");
                $stmt->execute([
                    ':student_number' => $student_number,
                    ':department' => $department, // department 값 삽입
                    ':password' => $hashed_password
                ]);
                $message = '<p class="message success">회원가입이 성공적으로 완료되었습니다! <a href="login.php">로그인</a>해주세요。</p>';
            } catch (PDOException $e) {
                $message = '<p class="message error">회원가입 중 오류가 발생했습니다: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規ユーザー登録 (New User Registration)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <h1>新規ユーザー登録</h1>
        <?php echo $message; ?>
        <form action="register_user.php" method="post" class="auth-form">
            <label for="student_number">学番:</label>
            <input type="text" id="student_number" name="student_number" required>
            <label for="department">学科:</label> <input type="text" id="department" name="department" required>
            <label for="password">パスワード:</label>
            <input type="password" id="password" name="password" required>
            <label for="confirm_password">パスワード確認:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit">登録</button>
        </form>
        <div class="auth-links">
            <p>既にアカウントをお持ちですか？ <a href="login.php">ログイン</a></p>
        </div>
    </div>
</body>
</html>