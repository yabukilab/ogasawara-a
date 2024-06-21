<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>ログインページ</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
<form action="05authenticate.php" method="POST">
    <div class="a">
        <div class="b">
            <p class="CIT">CIT sports</p>
            
            <class="logina">ログイン</p>
           
            <p class="number">学籍番号<br><input type="text" name="number">
            <?php if (isset($_GET['error']) && $_GET['error'] == 'student_number'): ?>
            <p style="color: red;">学籍番号が存在しません</p>
            <?php endif; ?>
            
            <p class="pass">パスワード<br><input type="password" name="pass"><br>
            <?php if (isset($_GET['error']) && $_GET['error'] == 'password'): ?>
            <p style="color: red;">パスワードが正しくありません</p>
            <?php endif; ?>

            <nav>
                <div class="link-container"><a href="decide.php">ログイン</a></div>
            </nav>
            
            <p class="signup">新規会員登録は<a href="" class="login">こちら</a></p>
        </div>
    </div>
</form>
</body>

</html>