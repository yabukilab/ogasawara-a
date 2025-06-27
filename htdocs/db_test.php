<?php
$host = 'localhost';
$dbname = 'mydb';
$username = 'root';  // 必要に応じて変更
$password = '';      // 必要に応じて変更

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    echo '✅ データベースに接続できました！';
} catch (PDOException $e) {
    echo '❌ 接続エラー: ' . $e->getMessage();
}
?>