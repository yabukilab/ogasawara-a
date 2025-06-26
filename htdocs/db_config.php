<?php
// 데이터베이스 연결 설정
$host = 'localhost';
$dbname = 'mydb'; // 실제 데이터베이스 이름으로 변경
$user = 'root';   // 실제 데이터베이스 사용자 이름으로 변경
$password = '';   // 실제 데이터베이스 비밀번호로 변경

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo 'データベース接続失敗: ' . $e->getMessage();
    exit();
}

// 학기 번호를 이름으로 변환하는 함수 (db_config.php에 포함하는 것이 일반적)
if (!function_exists('getTermName')) {
    function getTermName($term_num) {
        switch ($term_num) {
            case 1: return '前期'; // 전기
            case 2: return '後期'; // 후기
            default: return '不明'; // 불명
        }
    }
}
?>