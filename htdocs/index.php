<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <title>ログインページ</title>
    <link rel="stylesheet" href="global.css">
</head>

<body>
    <form action="" method="POST">
        <div class="A">
            <div class="B">
                <p class="CIT">CIT sports</p>
                
                <p class="login">ログイン</p>
            
                <p class="number">学籍番号<br>
                <input type="text" name="number">
                <?php if (isset($_GET['error']) && $_GET['error'] == 'student_number'): ?>
                <p style="color: red;">学籍番号が存在しません</p>
                <?php endif; ?>
                
                <p class="pass">パスワード<br>
                <input type="password" name="pass"><br>
                <?php if (isset($_GET['error']) && $_GET['error'] == 'password'): ?>
                <p style="color: red;">パスワードが正しくありません</p>
                <?php endif; ?>

                <!--<nav>-->
                    <div class="link-container">
                        <!--<a href="decide.php">-->
                            <input type="submit" value="ログイン" class="link-container">
                            <!--ログイン-->
                        <!--</a>-->
                    </div>
                <!--</nav>-->

                <!--pの変更前のクラスはsignup-->
                <!--aの変更前のクラスはlogin-->
                <p class="s">
                    新規会員登録は<a href="" class="kotira">こちら</a>
                </p>
                
                下記はあと消す
                <a href="02sinup.php">会員登録</a><br>
                <a href="03kakunin.php">登録確認</a><br>
                <a href="04.php">登録完了</a><br>
                <a href="06.php">予約確認</a><br>
                <a href="07.php">予約完了</a><br>
                <a href="table.php">表</a><br>
                <a href=""></a><br>

            </div>
        </div>
    </form>
</body>

</html>