<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>新規会員登録ページ</title>
    <link rel="stylesheet"  href="style2.css">
</head>

<body>
    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            <p class="signup">新規会員登録</p>
            <p class="a">以下の内容で登録が完了しました</p>
            <form action="new1.php"method="post">

            <p class="number">学籍番号<br>
            <?php print($_POST['number'].""); ?><br>

            <p class="pass">パスワード<br>
            <?php print($_POST['pass'].""); ?>

            </p>
            <p class="s">ログインは<a href="login.php"class="login">こちら</a></p>
            </form>
        </div>
    </div>
</body>

</html>