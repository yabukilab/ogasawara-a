<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>新規会員登録ページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
            <h1>CIT Sports</h1>
            <h2>新規会員登録</h2>

            <form action="03confirmation.php" method="post">
                <div class="student_number">学籍番号</div>
                <input type="text" name="student_number">
                <div class="pass">パスワード</div>
                <input type="password" name="password_hash">
                <br>
                <br>
                <input type="submit" value="確認する" class="button">
            </form>
            <h4>ログインは<a href="index.php">こちら</a></h4>
        </div>
    </body>
</html>