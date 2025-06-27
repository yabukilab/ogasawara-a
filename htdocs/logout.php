<?php
session_start(); // セッションを開始

// 全てのセッション変数を解除
$_SESSION = array();

// セッションクッキーを削除
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// セッションを破壊
session_destroy();

// ログアウト後のメッセージを表示するHTML
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログアウト (Logout)</title>
    <link rel="stylesheet" href="style2.css"> </head>
<body>
    <div class="auth-container">
        <h1>ログアウトしました</h1>
        <p class="message success">セッションを終了しました。またのご利用をお待ちしております。</p>
        <div class="auth-links">
            <p><a href="login.php">ログインページに戻る</a></p>
            <p><a href="register_user.php">新規ユーザー登録</a></p>
            <p><a href="index.php">時間割作成トップへ (ゲストとして閲覧)</a></p>
        </div>
    </div>
</body>
</html>