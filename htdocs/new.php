<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>新規会員登録ページ</title>
    <link rel="stylesheet"  href="style1.css">
</head>

    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            <p class="signup">新規会員登録</p>
            <p class="a">以下の内容でよろしければ登録ボタンを押してください</p>
            <form action="new.php"method="post">
            学籍番号<br>
            <?php print($_POST['number'].""); ?><br>
            
            パスワード<br>
            <?php print($_POST['pass'].""); ?>
            
            <nav>
                <input type="submit" value="登録する">
           </nav>
            <nav>
                <div class="link-container"><a href="new1.php">書き直す</a></div>
            </nav>
            </p>
            </form>
        </div>
    </div>
</body>

</html>