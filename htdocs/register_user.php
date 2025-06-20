<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー登録 (User Registration)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 500px; margin: 50px auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: calc(100% - 20px); /* Padding for input */
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
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ユーザー登録</h1>

        <?php
        $dbServer = '127.0.0.1';
        $dbName = 'mydb';
        $dbuser = 'testuser';
        $dbPass = 'pass';

        $message = '';
        $messageType = '';

        ffunction logError($msg) {
            file_put_contents(__DIR__ . '/user_registration_errors.log', date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
        }



        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $student_number = $_POST['student_number'] ?? ''; // student_number로 변경
            $department = $_POST['department'] ?? '';
            $password_input = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';

            if (empty($student_number) || empty($department) || empty($password_input) || empty($password_confirm)) {
                $message = '全ての項目を入力してください。';
                $messageType = 'error';
            } elseif ($password_input !== $password_confirm) {
                $message = 'パスワードと確認用パスワードが一致しません。';
                $messageType = 'error';
            } else {
                try {
                    $pdo = new PDO("mysql:host=$dbServer;dbname=$dbName;charset=utf8", $dbuser, $dbPass);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // student_number 중복 확인
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE student_number = :student_number");
                    $stmt->execute([':student_number' => $student_number]);
                    if ($stmt->fetchColumn() > 0) {
                        $message = 'この学籍番号は既に登録されています。';
                        $messageType = 'error';
                    } else {
                        // 비밀번호 해싱 (password_hash()는 강력히 권장되는 방법)
                        $hashed_password = password_hash($password_input, PASSWORD_DEFAULT);

                        // 사용자 정보 삽입 (password_hash 대신 password 컬럼 사용)
                        $stmt = $pdo->prepare("INSERT INTO users (student_number, department, password) VALUES (:student_number, :department, :password)");
                        $stmt->execute([
                            ':student_number' => $student_number,
                            ':department' => $department,
                            ':password' => $hashed_password // 해시된 비밀번호를 password 컬럼에 저장
                        ]);

                        $message = 'ユーザー登録が完了しました！ <a href="index.php">時間割登録画面へ</a>';
                        $messageType = 'success';
                    }

                } catch (PDOException $e) {
                    $message = 'データベースエラーが発生しました: ' . htmlspecialchars($e->getMessage());
                    $messageType = 'error';
                    logError("User registration DB error: " . $e->getMessage());
                } catch (Exception $e) {
                    $message = 'エラーが発生しました: ' . htmlspecialchars($e->getMessage());
                    $messageType = 'error';
                    logError("User registration error: " . $e->getMessage());
                }
            }
        }

        if ($message) {
            echo "<div class='message {$messageType}'>{$message}</div>";
        }
        ?>

        <form action="register_user.php" method="post">
            <div class="form-group">
                <label for="student_number">学籍番号:</label>
                <input type="text" id="student_number" name="student_number" required value="<?php echo htmlspecialchars($_POST['student_number'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="department">学科:</label>
                <input type="text" id="department" name="department" required value="<?php echo htmlspecialchars($_POST['department'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">パスワード:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">パスワード確認:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <div class="form-group">
                <input type="submit" value="登録">
            </div>
        </form>
        <a href="index.php" class="back-link">時間割登録画面へ戻る</a>
    </div>
</body>
</html>
