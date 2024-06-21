<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>新規会員登録完了ページ</title>
    <link rel="stylesheet"  href="global.css">
</head>

<body>
    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            <p class="signup">新規会員登録</p>
            <p class="a">以下の内容で登録が完了しました</p>

            <p class="number">学籍番号<br>
            
            <?php print($_POST['number']."");?>

            <p class="pass">パスワード<br>
                <input type="password" name="pass">
            </p>
            <p class="s">ログインは<a href=""class="login">こちら</a></p>
            
        </div>
    </div>
</body>

</html>