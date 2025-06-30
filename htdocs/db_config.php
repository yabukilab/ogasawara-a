<?php
// db_config.php
$dbServer = '127.0.0.1';
$dbName = 'mydb';
$dbUser = 'testuser';
$dbPass = 'pass'; // 실제 비밀번호로 변경하세요.

try {
    $dsn = "mysql:host=$dbServer;dbname=$dbName;charset=utf8";
    $db = new PDO($dsn, $dbUser, $dbPass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p style='color: red;'>データベース接続エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit(); // 스크립트 실행 중지
}
?>