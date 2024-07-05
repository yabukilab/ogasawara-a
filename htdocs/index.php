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
            <h1 class="cit">CIT Sports</h1>
            <h2>ログイン</h2>

            <form action="authenticate.php" method="post">
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'blank'): 
                ?>
                <div style="color: red;">入力されていない項目があります</div>
                <?php
                endif;
                ?>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'Discrepancy'): 
                ?>
                <div style="color: red;">学籍番号またはパスワードが一致しません</div>
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
            下記はあと消す<br>
            <a href="02signup.php">会員登録</a><br>
            <a href="03confirmation.php">登録確認</a><br>
            <a href="04completion.php">登録完了</a><br>
            <a href="05table.php">予約状況</a><br>
            <a href="06reservation.php">予約確認</a><br>
            <a href="07reserved.php">予約完了</a><br>
            <a href="top.php">データベース</a>
        </div>
    </body>
</html>