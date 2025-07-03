<?php
// セッションを開始します。スクリプトの冒頭で常に呼び出す必要があります。
session_start();
// db_config.php ファイルを読み込みます。これにより、$db オブジェクトと h() 関数が利用可能になります。
require_once 'db.php';

// エラーメッセージを格納するための変数を初期化します。
$error = '';

// HTTPリクエストがPOSTメソッドである場合、つまりログインフォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ユーザーからの入力を取得し、h() 関数でサニタイズ（無害化）します。
    // null合体演算子 (??) を使用して、POSTデータが存在しない場合のUndefined indexエラーを防ぎます。
    $student_number = h($_POST['student_number'] ?? '');
    $password = $_POST['password'] ?? ''; // パスワードはハッシュ化前に直接処理するため、h() は適用しません。

    // 入力フィールドが空でないかを検証します。
    if (empty($student_number) || empty($password)) {
        $error = "学番とパスワードをすべて入力してください。";
    } else {
        try {
            // db_config.php で定義されている $db オブジェクトを使用します。
            // ユーザー情報をデータベースから取得します。id, student_number, password, department を選択します。
            $stmt = $db->prepare("SELECT id, student_number, password, department FROM users WHERE student_number = :student_number");
            $stmt->bindParam(':student_number', $student_number);
            $stmt->execute();
            // 取得したユーザーデータを連想配列として $user 変数に格納します。
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // パスワードを検証します。
            // ユーザーが見つかり、かつ入力されたパスワードがデータベースに保存されているハッシュ化されたパスワードと一致するかを確認します。
            if ($user && password_verify($password, $user['password'])) {
                // ログイン成功！
                // セッション変数にユーザー情報を保存します。
                $_SESSION['student_number'] = $user['student_number']; // 学番をセッションに保存
                $_SESSION['department'] = $user['department'];       // 学科情報をセッションに保存
                $_SESSION['user_id'] = $user['id'];                 // ユーザーIDをセッションに保存 (index.php で user_id を使用するため)

                // ログイン成功後、index.php にリダイレクトします。
                header("Location: index.php");
                // リダイレクト後、スクリプトの実行を終了します。
                exit();
            } else {
                // ログイン失敗（学番またはパスワードが間違っています）。
                $error = "学番またはパスワードが間違っています。";
            }
        } catch (PDOException $e) {
            // データベース関連のエラーが発生した場合の処理です。
            error_log("Login DB Error: " . $e->getMessage()); // エラーの詳細をサーバーログに記録します。
            $error = "データベースエラーが発生しました。しばらくしてから再度お試しください。"; // ユーザーには一般的なエラーメッセージを表示します。
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
</body>
</html>