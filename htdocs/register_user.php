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
        $message = '<p class="message error">すべての項目を入力してください。</p>';
    } elseif ($password !== $confirm_password) {
        $message = '<p class="message error">パスワードが一致していません。</p>';
    } elseif (strlen($password) < 6) { // 최소 비밀번호 길이 설정
        $message = '<p class="message error">パスワードは6文字以上で設定してください。</p>';
    } else {
        // 학번 중복 확인
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE student_number = :student_number");
        $stmt->execute([':student_number' => $student_number]);
        if ($stmt->fetchColumn() > 0) {
            $message = '<p class="message error">この学籍番号はすでに使用されています。</p>';
        } else {
            // 비밀번호를 해싱하여 'password' 컬럼에 저장
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            try {
                // 'department' 컬럼을 포함하여 데이터 삽입
                $stmt = $db->prepare("INSERT INTO users (student_number, department, password) VALUES (:student_number, :department, :password)");
                $stmt->execute([
                    ':student_number' => $student_number,
                    ':department' => $department, // department 값 삽입
                    ':password' => $hashed_password
                ]);
                $message = '<p class="message success">ユーザ登録が完了しました。 <a href="login.php">ログイン</a>してください。</p>';
            } catch (PDOException $e) {
                $message = '<p class="message error">エラーが発生しました。: ' . htmlspecialchars($e->getMessage()) . '</p>';
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
    <link rel="stylesheet" href="style2.css">
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