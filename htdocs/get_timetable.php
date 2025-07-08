<?php
session_start();
require_once 'db.php'; // $db 객체 사용

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

if (!$grade || !$term) {
    $response['message'] = '学年または学期が指定されていません。';
    echo json_encode($response);
    exit;
}

// $db 객체가 유효한지 확인
if (!isset($db) || !($db instanceof PDO)) {
    $response['message'] = 'データベース接続オブジェクト ($db) が無効です。';
    error_log("データベース接続オブジェクト (\$db) が無効です。get_timetable.php");
    echo json_encode($response);
    exit();
}

try {
    $stmt = $db->prepare("
        SELECT ut.class_id, ut.day, ut.period,
               c.name AS class_name, c.credit, c.category1 AS category_name
        FROM user_timetables ut
        JOIN class c ON ut.class_id = c.id
        WHERE ut.user_id = :user_id
          AND ut.timetable_grade = :grade
          AND ut.timetable_term = :term
        ORDER BY ut.period ASC,
                 FIELD(ut.day, '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日') ASC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':grade', $grade, PDO::PARAM_INT);
    $stmt->bindParam(':term', $term, PDO::PARAM_STR);
    $stmt->execute();
    $timetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['timetable'] = $timetableData;

} catch (PDOException $e) {
    $response['message'] = '時間割のロード中にデータベースエラーが発生しました: ' . $e->getMessage();
    error_log("Get Timetable Error for user {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>