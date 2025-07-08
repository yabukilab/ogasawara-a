<?php
session_start();
require_once 'db.php'; // $db 객체 사용

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'classes' => []];

// $db 객체가 유효한지 확인
if (!isset($db) || !($db instanceof PDO)) {
    $response['message'] = 'データベース接続オブジェクト ($db) が無効です。';
    error_log("データベース接続オブジェクト (\$db) が無効です。get_classes.php");
    echo json_encode($response);
    exit();
}

try {
    $stmt = $db->query("SELECT id, name, grade, term, category1, category2, category3, credit FROM class ORDER BY name ASC");
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['classes'] = $classes;

} catch (PDOException $e) {
    $response['message'] = '授業リストのロード中にデータベースエラーが発生しました: ' . $e->getMessage();
    error_log("Get Classes Error: " . $e->getMessage());
}

echo json_encode($response);
?>