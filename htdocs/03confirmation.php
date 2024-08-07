<!--03confirmation.php-->
<!--サーバーからの情報は$_SERVERの配列にまとめられている-->
<!--リクエストメソッドがPOSTで送られてきていたら以下を行う-->
<?php
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $student_number=$_POST['student_number'];
    $password=$_POST['password'];
    $password2=$_POST['password2'];
}

if($student_number=="" || $password=="" || $password2==""){
    header("Location: 02signup.php?error=blank");
    exit();
}elseif(!(strlen($student_number)==7)){
    header("Location: 02signup.php?error=student_number");
    exit();
}elseif(strlen($password)<4 || strlen($password)>16){
    header("Location: 02signup.php?error=password");
    exit();
}elseif(!($password == $password2)){
    header("Location: 02signup.php?error=Discrepancy");
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>確認ページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
        <div><img src="CIT_Sports.jpg" alt="test" width="80%" height="80%"></div>
            <h2>確認画面</h2>

            <form action="register.php" method="post">
                <div>以下の内容でよろしければ<br>登録ボタンを押してください</div>
                <br>
                <div class="confirmation">
                    <div>学籍番号</div>
                <!--フォームなどからユーザのデータをブラウザに表示する場合，原則すべてのデータにhtmlspecialchars()関数を使う-->
                    <div style="color:red;">
                    <?php echo htmlspecialchars($student_number); ?>
                    </div>
                    <input type="hidden" name="student_number" value="<?php echo htmlspecialchars($student_number); ?>">
                    <div>パスワード</div>
                <!--str_repeat・・・複数回繰り返す-->
                <!--strlen($password_hash)・・・$password_hashの文字数を数える-->
                    <div style="color:red;">
                    <?php echo str_repeat('*',strlen($password)); ?>
                    </div>
                    <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>">
                </div>
                <br>
                <br>
                <input type="button" value="書き直す" onclick="history.back()" class="button">
                <br>
                <br>
                <input type="submit" value="登録する" class="button">
            </form>
        </div>
    </body>
</html>