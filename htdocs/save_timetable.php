<?php
session_start();
require_once 'db.php'; // DB 연결

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'ログインしていません。';
    echo json_encode($response);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['timetable']) || !is_array($data['timetable']) || !isset($data['grade']) || !isset($data['term'])) {
    $response['message'] = '無効なデータです。';
    echo json_encode($response);
    exit;
}

$timetable = $data['timetable'];
$timetable_grade = $data['grade'];
$timetable_term = $data['term'];

try {
    $pdo->beginTransaction();

    // 1. 해당 user_id, timetable_grade, timetable_term 에 해당하는 모든 시간표를 삭제 (핵심!)
    $stmt = $pdo->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND timetable_grade = :grade AND timetable_term = :term");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
    $stmt->execute();

    // 2. 새로운 시간표 데이터 삽입
    $stmt = $pdo->prepare("INSERT INTO user_timetables (user_id, timetable_grade, timetable_term, class_id, day, period) VALUES (:user_id, :grade, :term, :class_id, :day, :period)");

    foreach ($timetable as $item) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
        $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
        $stmt->bindParam(':class_id', $item['class_id'], PDO::PARAM_INT);
        $stmt->bindParam(':day', $item['day'], PDO::PARAM_STR);
        $stmt->bindParam(':period', $item['period'], PDO::PARAM_INT);
        $stmt->execute();
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = '時間割が正常に保存されました。';

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = '時間割の保存中にエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable Error: " . $e->getMessage()); // 에러 로깅
}

echo json_encode($response);
?>