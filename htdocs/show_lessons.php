<?php
ini_set('display_errors', 1); // 이 줄이 있는지 확인
ini_set('display_startup_errors', 1); // 이 줄이 있는지 확인
error_reporting(E_ALL); // 이 줄이 있는지 확인

// 세션을 시작합니다.
session_start();
// 데이터베이스 연결 파일을 포함합니다.
require_once 'db.php'; // 이 줄이 있는지 확인

// ... (나머지 show_lessons.php 코드)

try {
    // ... (SQL 쿼리 구성 로직)

    // 디버깅을 위한 error_log 구문이 있는지 확인 (이 부분이 핵심)
    error_log("DEBUG show_lessons.php: SQL Query: " . $sql);
    error_log("DEBUG show_lessons.php: SQL Params: " . print_r($params, true));
    error_log("DEBUG show_lessons.php: SQL Types: " . $types);

    $stmt = $db->prepare($sql); // $db 로 되어있는지 확인!
    // ...

} catch (PDOException $e) {
    // 이 catch 블록이 있는지, 그리고 error_log 구문이 있는지 확인
    error_log("授業データ読み込みDBエラー (show_lessons.php): " . $e->getMessage());
    // ...
}
// ...
?>