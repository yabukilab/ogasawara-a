<?php
session_start();
require_once 'db.php';
require_once 'functions/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if (login($db, $student_number, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = '学籍番号またはパスワードが間違っています。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h1>ログイン</h1>
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="student_number">学籍番号</label>
            <input type="text" name="student_number" id="student_number" required>

            <label for="password">パスワード</label>
            <input type="password" name="password" id="password" required>

            <button type="submit">ログイン</button>
        </form>
        <p><a href="register.php">新規登録はこちら</a></p>
    </div>
</body>
</html>
