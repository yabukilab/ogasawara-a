<?php
session_start();
if(isset($_SESSION['student_number'])){
    $student_number=$_SESSION['student_number'];
    unset($_SESSION['student_number']);
}else{
    $student_number='不明';
}
if(isset($_SESSION['password'])){
    $password=$_SESSION['password'];
    unset($_SESSION['password']);
}else{
    $password='不明';
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
            <h2>新規会員登録</h2>

                <div>以下の内容で登録が完了しました</div>
                <br>
                <div class="confirmation">
                    <div class="number">学籍番号</div>
                    <div style="color:red;">
                    <?php echo htmlspecialchars($student_number); ?>
                    </div>
                    <div class="pass">パスワード</div>
                    <div style="color:red;">
                    <?php
                    if($password=="不明"){
                        echo htmlspecialchars($password);
                    }else{
                    echo str_repeat('*',strlen($password)); 
                    }
                    ?>
                    </div>
                </div>
            <h4>ログインは<a href="index.php">こちら</a></h4>
            
        </div>
    </body>
</html>