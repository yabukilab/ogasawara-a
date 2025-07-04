<?php
// セッションを開始します。スクリプトの冒頭で常に呼び出す必要があります。
session_start();

// db.php ファイルを読み込みます。これにより、$db オブジェクトと h() 関数が利用可能になります。
require_once 'db.php';

// エラーまたは成功メッセージを格納するための変数を初期化します。
$message = '';
$message_type = ''; // 'success' または 'error'

// HTTPリクエストがPOSTメソッドである場合、つまり新規ユーザー登録フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ユーザーからの入力を取得し、h() 関数でサニタイズ（無害化）します。
    // 学番、学科、パスワード、パスワード確認のみを取得
    $student_number = h($_POST['student_number'] ?? '');
    $department = h($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 入力フィールドがすべて空でないかを検証します。
    if (empty($student_number) || empty($department) || empty($password) || empty($confirm_password)) {
        $message = "全ての項目を入力してください。";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        // パスワードとパスワード確認が一致するかを確認します。
        $message = "パスワードが一致しません。";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        // パスワードの最小長を確認します（例: 6文字以上）。
        $message = "パスワードは最低6文字以上である必要があります。";
        $message_type = "error";
    } else {
        try {
            // 学番が既に存在するかを確認します。
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE student_number = :student_number");
            $stmt->bindParam(':student_number', $student_number);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $message = "この学番は既に登録されています。別の学番を使用してください。";
                $message_type = "error";
            } else {
                // パスワードをハッシュ化します（非常に重要！）。
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // ユーザー情報をデータベースの users テーブルに挿入します。
                // student_number, department, password のみ挿入
                $stmt = $db->prepare("INSERT INTO users (student_number, department, password) VALUES (:student_number, :department, :password)");
                $stmt->bindParam(':student_number', $student_number);
                $stmt->bindParam(':department', $department);
                $stmt->bindParam(':password', $hashed_password);

                if ($stmt->execute()) {
                    $message = "新規ユーザー登録が正常に完了しました。これでログインできます。";
                    $message_type = "success";
                    // 登録成功後、ログインページにリダイレクトすることも可能です。
                    // header("Location: login.php");
                    // exit();
                } else {
                    $message = "新規ユーザー登録中にエラーが発生しました。再度お試しください。";
                    $message_type = "error";
                }
            }
        } catch (PDOException $e) {
            // データベース関連のエラーが発生した場合の処理です。
            error_log("新規ユーザー登録DBエラー: " . $e->getMessage()); // エラーの詳細をサーバーログに記録します。
            $message = "データベースエラーが発生しました。しばらくしてから再度お試しください。"; // ユーザーには一般的なエラーメッセージを表示します。
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>新規ユーザー登録 (Register)</title>
    <link rel="stylesheet" href="style2.css"> 
</head>
<body>
    <div class="auth-container">
        <h1>新規ユーザー登録</h1>
        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo h($message); ?></p>
        <?php endif; ?>
        <form action="register_user.php" method="post" class="auth-form">
            <label for="student_number">学番:</label> 
            <input type="text" id="student_number" name="student_number" required value="<?php echo h($student_number ?? ''); ?>">

            <label for="department">学科:</label>
            <input type="text" id="department" name="department" required value="<?php echo h($department ?? ''); ?>">

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

    <!-- auth_scripts.js ファイルも、メールアドレスのバリデーションロジックを削除するように修正が必要です。 -->
    <!-- このスクリプトは、HTML要素が全てロードされた後に実行されるように、</body>タグの直前に配置します。 -->
    <script src="auth_scripts.js" defer></script>
</body>
</html>