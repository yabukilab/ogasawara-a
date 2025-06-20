<?php
session_start();

$host = '127.0.0.1';
$dbName = 'mydb';
$user = 'root';
$password = '';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? ''; // student_number로 변경
    $password_input = $_POST['password'] ?? '';

    if (empty($student_number) || empty($password_input)) {
        $message = '学籍番号とパスワードを入力してください。';
        $messageType = 'error';
    } else {
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // student_number로 조회, password 컬럼에서 해시된 비밀번호 가져오기
            $stmt = $pdo->prepare("SELECT id, student_number, password FROM users WHERE student_number = :student_number");
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
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 400px; margin: 50px auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1em;
        }
        .form-group input[type="submit"] {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1.1em;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .form-group input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .register-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .register-link:hover {
            text-decoration: underline;
        }
    </style>
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