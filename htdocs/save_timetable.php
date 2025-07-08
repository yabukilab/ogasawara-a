<?php
session_start();
require_once 'db.php'; // DB 연결 (여기서 $db 객체가 생성됨)

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

// $db 객체가 유효한지 확인
if (!isset($db) || !($db instanceof PDO)) {
    $response['message'] = 'データベース接続オブジェクト ($db) が無効です。';
    error_log("データベース接続オブジェクト (\$db) が無効です。save_timetable.php");
    echo json_encode($response);
    exit(); // 스크립트 종료
}

try {
    // 트랜잭션 시작
    $db->beginTransaction(); // $pdo 대신 $db 사용

    // 1. 해당 user_id, timetable_grade, timetable_term 에 해당하는 모든 시간표를 삭제 (핵심!)
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND timetable_grade = :grade AND timetable_term = :term"); // $pdo 대신 $db 사용
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
    $stmt->execute();

    // 2. 새로운 시간표 데이터 삽입
    $stmt = $db->prepare("INSERT INTO user_timetables (user_id, timetable_grade, timetable_term, class_id, day, period) VALUES (:user_id, :grade, :term, :class_id, :day, :period)"); // $pdo 대신 $db 사용

    foreach ($timetable as $item) {
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
        $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
        $stmt->bindParam(':class_id', $item['class_id'], PDO::PARAM_INT);
        $stmt->bindParam(':day', $item['day'], PDO::PARAM_STR);
        $stmt->bindParam(':period', $item['period'], PDO::PARAM_INT);
        $stmt->execute();
    }

    // 트랜잭션 커밋
    $db->commit(); // $pdo 대신 $db 사용
    $response['success'] = true;
    $response['message'] = '時間割が正常に保存されました。';

} catch (PDOException $e) {
    // 오류 발생 시 롤백
    $db->rollBack(); // $pdo 대신 $db 사용
    $response['message'] = '時間割の保存中にエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable Error: " . $e->getMessage()); // 에러 로깅
}

echo json_encode($response);
?>