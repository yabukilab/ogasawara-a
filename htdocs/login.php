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
            $pdo = new PDO("mysql:host=$dbServer;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 조회된 password 컬럼의 해시 값과 입력된 비밀번호를 password_verify()로 비교
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['student_number'] = $user['student_number'];
            header('Location: index.php'); // 로그인 성공 시 메인 페이지로 리다이렉트
            exit;
        } else {
            $message = '<p class="message error"> 잘못された学番またはパスワードです。</p>';
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