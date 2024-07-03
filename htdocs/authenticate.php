
<?php
session_start();
require('db.php');

if($_SERVER["REQUEST_METHOD"]=="POST"){
    $student_number=$_POST['student_number'];
    $password=$_POST['password'];
    if($student_number=="" || $password==""){
        header("Location:index.php?error=blank");
        exit();
    }else{
        $sql = 'SELECT password_hash FROM users WHERE student_number = :student_number'; // SQL文を構成
        $sth = $db->prepare($sql); // SQL文を実行変数へ投入
        $sth->bindParam(':student_number', $student_number); // ユーザIDを実行変数に挿入
        $sth->execute(); // SQLの実行
        $result = $sth->fetch(); // 処理結果の取得

        if($result!=0 && password_verify($password,$result['password_hash'])){
            $_SESSION['student_number']=$student_number;
            header("Location:05table.php");
            exit();
        }else{
            header("Location:index.php?error=Discrepancy");
            exit();
        }

    }
}
?>