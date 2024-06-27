<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>新規会員登録ページ</title>
    <link rel="stylesheet" href="new20.css">
</head>

<body>

    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            <p class="signup">新規会員登録</p>
            <p class="a">以下の内容でよろしければ登録ボタンを押してください</p>
            <form action="new1.php" method="post">
                学籍番号<br>
                <?php echo htmlspecialchars($_POST['number'], ENT_QUOTES, 'UTF-8'); ?><br>

                パスワード<br>
                <?php echo htmlspecialchars($_POST['pass'], ENT_QUOTES, 'UTF-8'); ?>

                <nav>
                    <input type="submit" value="登録する" class="submit-button">
                </nav>
            </form>
            <form action="01signup.php" method="post">
                <nav>
                    <input type="submit" value="書き直す" class="submit-button">
                </nav>
            </form>
        </div>
    </div>

</body>

</html>
