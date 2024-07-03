<?php
session_start();
require('db.php');

// IDの重複チェック
$sql = 'SELECT * FROM users where student_number = :student_number'; // SQL文を構成
$sth = $db->prepare($sql); // SQL文を実行変数へ投入
$sth->bindParam(':student_number', $_POST['student_number']); // ユーザIDを実行変数に挿入
$sth->execute(); // SQLの実行
$result = $sth->fetch(); // 処理結果の取得
if($result!=0){ // IDが重複する場合にエラーメッセージを表示する処理
  header("Location:02signup.php?error=Duplicates");
  exit();
} else {
    $password_hash = password_hash($_POST['password'],PASSWORD_DEFAULT);

    $sql = 'INSERT INTO users (student_number, password_hash) VALUES(:student_number, :password_hash)'; // SQL文を構成
    $sth = $db->prepare($sql); // SQL文を実行変数へ投入
    $sth->bindParam(':student_number', $_POST['student_number']); // ユーザIDを実行変数に挿入

    $sth->bindParam(':password_hash', $password_hash); // パスワードを実行変数に挿入

    $_SESSION['student_number'] = $_POST['student_number'];
    $_SESSION['password'] = $_POST['password'];
    $sth->execute(); // SQLの実行
    header("Location:04completion.php");
}
?>