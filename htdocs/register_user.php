<?php
// DB接続情報
$host = 'localhost';
$dbname = 'mydb';
$username = 'root';
$password = ''; // パスワードは環境に合わせて変更してください

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $department = $_POST['department'] ?? '';
    $password_input = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password_input !== $confirm_password) {
        $message = 'パスワードが一致しません。';
    } elseif (empty($student_number) || empty($department) || empty($password_input)) {
        $message = 'すべての項目を入力してください。';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (student_number, department, password) VALUES (?, ?, ?)");
            $stmt->execute([$student_number, $department, $hashed_password]);

            $message = 'ユーザ登録が完了しました。';
        } catch (PDOException $e) {
            $message = 'エラー: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>ユーザ登録</title>
</head>
<body>
    <h2>新規ユーザ登録</h2>
    <form method="post" action="register_user.php">
        <label>学籍番号: <input type="text" name="student_number" required></label><br><br>
        <label>学科: <input type="text" name="department" required></label><br><br>
        <label>パスワード: <input type="password" name="password" required></label><br><br>
        <label>パスワード確認: <input type="password" name="confirm_password" required></label><br><br>
        <input type="submit" value="登録">
    </form>
    <p style="color:red;"><?php echo htmlspecialchars($message); ?></p>
</body>
</html>