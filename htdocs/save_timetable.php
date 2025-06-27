<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['student_number'])) {
    $response['message'] = 'ログインしていません。';
    echo json_encode($response);
    exit();
}

$student_number = $_SESSION['student_number'];
$input_data = file_get_contents('php://input');
$timetable_data = json_decode($input_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = '無効なJSONデータです。';
    $response['error_details'] = json_last_error_msg();
    echo json_encode($response);
    exit();
}

// timetable_data가 비어있을 경우, 해당 학년의 모든 시간표를 삭제
if (empty($timetable_data)) {
    // 클라이언트에서 빈 배열을 보낼 때 `grade`도 함께 보내도록 수정하는 것이 가장 안전함.
    // 현재 `confirmTimetable` 함수에서 시간표가 비어있으면 alert를 띄우므로, 이 경우는 발생하지 않을 것.
    // 만약 빈 시간표를 저장해야 한다면, JS에서 `grade`를 추가로 보내야 함.
    // 지금은 `timetable_data`가 비어있지 않다고 가정.
    // (만약 빈 시간표 저장이 필요하다면, 이 부분을 주석 처리하고 클라이언트에서 grade를 추가로 보내도록 수정해야 합니다.)
    $response['message'] = '登録する授業がありません。';
    echo json_encode($response);
    exit();
}

try {
    $db->beginTransaction();

    // 현재 학년의 모든 기존 시간표 데이터 삭제
    // 모든 수업은 같은 학년이므로 첫 번째 데이터에서 학년을 가져옴
    $grade_to_delete = $timetable_data[0]['grade'];
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE student_number = :student_number AND grade = :grade");
    $stmt->execute([':student_number' => $student_number, ':grade' => $grade_to_delete]);

    // 새로운 시간표 데이터 삽입
    // is_primary 컬럼이 DB에 남아있을 경우를 대비하여 :is_primary 포함
    $stmt = $db->prepare("INSERT INTO user_timetables (student_number, grade, day, period, class_id, is_primary) VALUES (:student_number, :grade, :day, :period, :class_id, :is_primary)");

    foreach ($timetable_data as $entry) {
        $stmt->execute([
            ':student_number' => $student_number,
            ':grade' => $entry['grade'],
            ':day' => $entry['day'],
            ':period' => $entry['period'],
            ':class_id' => $entry['class_id'],
            ':is_primary' => $entry['is_primary'] // is_primary 값 수신 및 삽입 (JS에서 0으로 보냄)
        ]);
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = '時間割が正常に登録されました。';

} catch (PDOException $e) {
    $db->rollBack();
    $response['message'] = '時間割の登録に失敗しました。';
    $response['error_details'] = $e->getMessage();
    error_log("Timetable save failed: " . $e->getMessage());
}

echo json_encode($response);
?>