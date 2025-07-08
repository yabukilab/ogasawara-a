<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'classes' => []];

$gradeFilter = $_GET['grade'] ?? '';
$termFilter = $_GET['term'] ?? '';

try {
    $sql = "SELECT id, name, grade, term, credit, category1, category2, category3 FROM class WHERE 1";
    $params = [];

    if (!empty($gradeFilter)) {
        $sql .= " AND grade = :grade";
        $params[':grade'] = $gradeFilter;
    }
    if (!empty($termFilter)) {
        $sql .= " AND term = :term";
        $params[':term'] = $termFilter;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['classes'] = $classes;

} catch (PDOException $e) {
    $response['message'] = '授業リストの取得中にエラーが発生しました: ' . $e->getMessage();
    error_log("Get Classes Error: " . $e->getMessage());
}

echo json_encode($response);
?>