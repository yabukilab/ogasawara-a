<!--index.php-->
<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>ログインページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
            <div><img src="CIT_Sports.jpg" alt="test" width="80%" height="80%"></div>
            <h2>ログイン</h2>

            <form action="authenticate.php" method="post">
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'blank'): 
                ?>
                <div style="color: red; font-weight: bold;">入力されていない項目があります</div>
                <?php
                endif;
                ?>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'Discrepancy'): 
                ?>
                <div style="color: red; font-weight: bold;">学籍番号またはパスワードが一致しません</div>
                <?php
                endif;
                ?>
                <div class="number">学籍番号</div>
                <input type="text" name="student_number">
                <div class="pass">パスワード</div>
                <input type="password" name="password">
                <br>
                <br>
                <input type="submit" value="ログイン" class="button">
            </form>
            <h4>新規会員登録は<a href="02signup.php">こちら</a></h4>
        </div>
    </body>
</html>