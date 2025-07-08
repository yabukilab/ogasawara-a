<?php
require_once 'db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($student_number && $password) {
        // 学籍番号の重複チェック
        $stmt = $db->prepare("SELECT id FROM users WHERE student_number = ?");
        $stmt->execute([$student_number]);
        if ($stmt->fetch()) {
            $error = 'この学籍番号は既に登録されています。';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (student_number, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$student_number, $hash])) {
                $success = '登録が完了しました。ログインしてください。';
            } else {
                $error = '登録に失敗しました。';
            }
        }
    } else {
        $error = 'すべての項目を入力してください。';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>新規登録</title>
    <link rel="stylesheet" href="css/register.css">
    <script src="js/register.js" defer></script>
</head>
<body>
    <div class="register-container">
        <h1>新規アカウント登録</h1>
        <?php if ($success): ?>
            <p class="success"><?= htmlspecialchars($success) ?></p>
        <?php elseif ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post" id="registerForm">
            <label for="student_number">学籍番号</label>
            <input type="text" name="student_number" id="student_number" required>

            <label for="password">パスワード</label>
            <input type="password" name="password" id="password" required>

            <label for="confirm_password">パスワード（確認）</label>
            <input type="password" id="confirm_password" required>

            <p id="mismatch-message" class="error" style="display: none;">パスワードが一致しません</p>

            <button type="submit" id="registerBtn" disabled>登録</button>
        </form>
        <p><a href="login.php">ログインはこちら</a></p>
    </div>
</body>
</html>
