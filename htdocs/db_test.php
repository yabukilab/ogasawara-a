<?php
// データベース接続情報を設定
$host     = isset($_ENV['MYSQL_SERVER'])    ? $_ENV['MYSQL_SERVER']    : '127.0.0.1';
$username = isset($_SERVER['MYSQL_USER'])   ? $_SERVER['MYSQL_USER']   : 'testuser';
$password = isset($_SERVER['MYSQL_PASSWORD']) ? $_SERVER['MYSQL_PASSWORD'] : 'pass';
$dbname   = isset($_SERVER['MYSQL_DB'])     ? $_SERVER['MYSQL_DB']     : 'mydb';

try {
    // 接続開始
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo '✅ データベースに接続できました！';
} catch (PDOException $e) {
    echo '❌ 接続エラー: ' . $e->getMessage();
}
?>