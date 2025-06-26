<?php
// 데이터베이스 연결 설정
$host = 'localhost';
$dbname = 'mydb'; // 실제 데이터베이스 이름으로 변경
$user = 'root';   // 실제 데이터베이스 사용자 이름으로 변경
$password = '';   // <-- 여기에 실제 root 비밀번호를 입력하거나, 비밀번호가 없다면 '' (빈 문자열)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password); // charset은 utf8mb4 권장
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 사용자에게는 일반적인 에러 메시지, 실제 에러는 서버 로그에 기록
    error_log("Database connection error: " . $e->getMessage());
    die('データベース接続に失敗しました。管理者にお問い合わせください。');
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