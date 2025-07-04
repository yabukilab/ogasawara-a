<?php
// (変更なし: PHP処理ロジック部分)
session_start();
require_once 'db.php';
$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_number = h($_POST['student_number'] ?? '');
    $password = $_POST['password'] ?? '';
    // ... (PHPロジックの続き)
    try {
        $stmt = $db->prepare("SELECT id, student_number, password, department FROM users WHERE student_number = :student_number");
        $stmt->bindParam(':student_number', $student_number);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['student_number'] = $user['student_number'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['user_id'] = $user['id'];
            header("Location: index.php");
            exit();
        } else {
            $error = "学番またはパスワードが間違っています。";
        }
    } catch (PDOException $e) {
        error_log("Login DB Error: " . $e->getMessage());
        $error = "データベースエラーが発生しました。しばらくしてから再度お試しください。";
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン (Login)</title>
    <link rel="stylesheet" href="style2.css"> </head>
<body>
    <div class="auth-container">
        <h1>ログイン</h1>
        <?php if (!empty($error)): ?>
            <p class="message error"><?php echo h($error); ?></p>
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

    <script src="auth_scripts.js"></script> 
</body>
</html>