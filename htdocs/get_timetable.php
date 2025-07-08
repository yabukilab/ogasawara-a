<?php
session_start();
require_once 'db.php'; // DB 연결

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'timetable' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'ログインしていません。';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$grade = $_GET['grade'] ?? null;
$term = $_GET['term'] ?? null;

if (empty($grade) || empty($term)) {
    $response['message'] = '学年と学期を指定してください。';
    echo json_encode($response);
    exit;
}

try {
    // user_timetables와 class 테이블을 JOIN하여 수업 이름, 학점, 카테고리 정보도 함께 가져옴
    $stmt = $pdo->prepare("
        SELECT ut.class_id, ut.day, ut.period,
               c.name AS class_name, c.credit, c.category1 AS category_name
        FROM user_timetables ut
        JOIN class c ON ut.class_id = c.id
        WHERE ut.user_id = :user_id AND ut.timetable_grade = :grade AND ut.timetable_term = :term
        ORDER BY ut.period ASC, ut.day ASC
    ");

    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
    $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    $stmt->execute();
    $timetable_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['timetable'] = $timetable_data;

} catch (PDOException $e) {
    $response['message'] = '時間割の取得中にエラーが発生しました: ' . $e->getMessage();
    error_log("Get Timetable Error: " . $e->getMessage()); // 에러 로깅
}

echo json_encode($response);
?>