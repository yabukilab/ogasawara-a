<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ログインが必要です。']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['userId']) || $input['userId'] != $current_user_id || !isset($input['grade']) || !isset($input['timetableData'])) {
    echo json_encode(['status' => 'error', 'message' => '無効なデータです。']);
    exit();
}

$user_id = $input['userId'];
$grade_to_save = $input['grade']; // 이 값은 user_timetables 테이블의 grade 컬럼에 저장됩니다.
$timetable_data = $input['timetableData'];

try {
    $db->beginTransaction();

    // 해당 사용자 및 학년의 기존 시간표 데이터를 모두 삭제
    // 이렇게 함으로써 클라이언트에서 전달된 데이터로 완전히 덮어쓸 수 있습니다.
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':grade', $grade_to_save);
    $stmt->execute();

    // 새로운 시간표 데이터 삽입
    $insert_stmt = $db->prepare("INSERT INTO user_timetables (user_id, day, period, class_id, grade) VALUES (:user_id, :day, :period, :class_id, :grade)");

    foreach ($timetable_data as $item) {
        // 클라이언트에서 전달된 class_id, day, period, grade를 그대로 사용
        // period는 시작 교시만 전달됨 (예: 1교시 시작 수업은 period=1)
        $insert_stmt->bindParam(':user_id', $user_id);
        $insert_stmt->bindParam(':day', $item['day']);
        $insert_stmt->bindParam(':period', $item['period']); // 시간표에서 시작 교시
        $insert_stmt->bindParam(':class_id', $item['class_id']);
        $insert_stmt->bindParam(':grade', $item['grade']); // 이 수업이 속하는 학년 (시간표 학년 필터 값)
        $insert_stmt->execute();
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました。']);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("時間割保存エラー: " . $e->getMessage()); // 에러 로그에 기록
    echo json_encode(['status' => 'error', 'message' => '時間割の保存中にエラーが発生しました。']);
}
?>