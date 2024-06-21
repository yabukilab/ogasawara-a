<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>新規会員登録ページ</title>
    <link rel="stylesheet"  href="global.css">
</head>

<body>
    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            <p class="signup">新規会員登録</p>
            <p class="a">以下の内容でよろしければ登録ボタンを押してください</p>

            <p class="number">学籍番号<br>
            
            <?php print($_POST['number']."");?>

            <p class="pass">パスワード<br>
                <input type="password" name="pass">
            </p>
            <a input type="new1.php" class="btn btn--a btn--radius">書き直す</a><br><br>
            <a href="" class="btn btn--blue btn--radius">登録</a>
            
        </div>
    </div>
</body>

</html>