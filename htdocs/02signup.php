<!--02signup.php-->
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

            <form action="03confirmation.php" method="POST">
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'blank'): 
                ?>
                <div style="color: red;">入力されていない項目があります</div>
                <?php
                endif;
                ?>

                <div class="student_number">学籍番号</div>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'student_number'): 
                ?>
                <div style="color: red;">学籍番号は7文字で入力してください</div>
                <?php
                endif;
                ?>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'Duplicates'):
                ?>
                <div style="color: red;">この学籍番号は既に登録されています</div>
                <?php
                endif;
                ?>
                <input type="text" name="student_number">

                <div class="pass">パスワード</div>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'password'): 
                ?>
                <div style="color: red;">パスワードは4桁以上16桁以下で入力してください</div>
                <?php
                endif;
                ?>
                <input type="password" name="password">


                <div class="pass">パスワード（再入力）</div>
                <?php
                if(isset($_GET['error']) && $_GET['error'] == 'Discrepancy'): 
                ?>
                <div style="color: red;">パスワードが一致していません</div>
                <?php
                endif;
                ?>
                <input type="password" name="password2">


                <br>
                <br>
                <input type="submit" value="確認する" class="button">
            </form>
            <h4>ログインは<a href="index.php">こちら</a></h4>
        </div>
    </body>
</html>
