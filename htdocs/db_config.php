<?php
// db_config.php

define('DB_NAME', 'mydb');
define('DB_USER', 'root');
// PHPMyAdmin에서 비밀번호 없이 로그인된다면, DB_PASS를 '' (빈 문자열)로 설정합니다.
define('DB_PASS', 'pass'); // <-- 이 부분을 이렇게 수정하세요!

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die('データベース接続に失敗しました。管理者にお問い合わせください。');
}

if (!function_exists('getTermName')) {
    function getTermName($term_num) {
        switch ($term_num) {
            case 1: return '前期';
            case 2: return '後期';
            default: return '不明';
        }
    }
}
?>