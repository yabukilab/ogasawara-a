<?php
session_start();
require_once 'db_config.php'; // DB 연결

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーがログインしていません。']);
    exit();
}

$userId = $_SESSION['user_id'];
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['timetableData']) || !isset($data['grade']) || !isset($data['userId'])) {
    echo json_encode(['status' => 'error', 'message' => '無効なデータが提供されました。']);
    exit();
}

$timetableData = $data['timetableData'];
$gradeToSave = (int)$data['grade']; // 저장할 학년 정보

try {
    $db->beginTransaction();

    // 해당 사용자의 해당 학년에 대한 기존 시간표 데이터를 모두 삭제
    $deleteSql = "DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade";
    $stmtDelete = $db->prepare($deleteSql);
    $stmtDelete->execute([':user_id' => $userId, ':grade' => $gradeToSave]);

    // 새로운 시간표 데이터 삽입
    $insertSql = "INSERT INTO user_timetables (user_id, class_id, day, period, grade) VALUES (:user_id, :class_id, :day, :period, :grade)";
    $stmtInsert = $db->prepare($insertSql);

    foreach ($timetableData as $item) {
        $stmtInsert->execute([
            ':user_id' => $userId,
            ':class_id' => $item['class_id'],
            ':day' => $item['day'],
            ':period' => $item['period'],
            ':grade' => $gradeToSave
        ]);
    }

    $db->commit();
    echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました。']);

} catch (PDOException $e) {
    $db->rollBack();
    error_log("Save Timetable DB Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'データベースエラー: ' . $e->getMessage()]);
} catch (Exception $e) {
    $db->rollBack();
    error_log("Save Timetable General Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'サーバーエラーが発生しました。']);
}
?>