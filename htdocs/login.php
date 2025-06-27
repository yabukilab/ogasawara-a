<?php
session_start();
// db_config.php ファイルを読み込みます。これにより、$pdo オブジェクトと h() 関数が利用可能になります。
require_once 'db_config.php';
// エラーメッセージを格納するための変数を初期化します。
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? ''; // student_number로 변경
    $password_input = $_POST['password'] ?? '';

    if (empty($student_number) || empty($password_input)) {
        $message = '学籍番号とパスワードを入力してください。';
        $messageType = 'error';
    } else {
        try {
            $db = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // student_number로 조회, password 컬럼에서 해시된 비밀번호 가져오기
            $stmt = $db->prepare("SELECT id, student_number, password FROM users WHERE student_number = :student_number");
            $stmt->execute([':student_number' => $student_number]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

            // user_data가 존재하고, 비밀번호가 일치하는지 확인 (password 컬럼 사용)
            if ($user_data && password_verify($password_input, $user_data['password'])) {
                // 로그인 성공
                $_SESSION['student_number'] = $user_data['student_number']; // 세션에 학번 저장
                $_SESSION['user_db_id'] = $user_data['id']; // DB의 실제 ID도 저장 (내부 식별용)
                header('Location: index.php'); // 성공 시 index.php로 리다이렉트
                exit;
            } else {
                $message = '無効な学籍番号またはパスワードです。';
                $messageType = 'error';
            }

        } catch (PDOException $e) {
            $message = 'データベースエラーが発生しました: ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
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
    <link rel="stlesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>ログイン</h1>
        <?php
        if ($message) {
            echo "<div class='message {$messageType}'>{$message}</div>";
        }
        ?>

        <form action="login.php" method="post">
            <div class="form-group">
                <label for="student_number">学籍番号:</label>
                <input type="text" id="student_number" name="student_number" required>
            </div>
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <input type="submit" value="ログイン">
            </div>
        </form>
        <a href="register_user.php" class="register-link">新規ユーザー登録はこちら</a>
    </div>
</body>
</html>