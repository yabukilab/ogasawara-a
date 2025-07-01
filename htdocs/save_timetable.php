<?php
session_start();
require_once 'db_config.php'; // 데이터베이스 설정 파일 포함

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => '不明なエラー'];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'ログインしてください。';
    echo json_encode($response);
    exit();
}

$current_user_id = $_SESSION['user_id'];

$input_data = json_decode(file_get_contents('php://input'), true);

if (!isset($input_data['userId']) || !isset($input_data['grade']) || !isset($input_data['timetableData']) || !is_array($input_data['timetableData'])) {
    $response['message'] = '無効な入力データです。';
    echo json_encode($response);
    exit();
}

$userId = $input_data['userId'];
$gradeToSave = $input_data['grade']; // 이 학년의 시간표를 저장/업데이트

// 요청된 userId가 현재 로그인된 사용자와 일치하는지 확인 (보안 강화)
if ($userId !== $current_user_id) {
    $response['message'] = '無効なユーザーIDです。';
    echo json_encode($response);
    exit();
}

$timetableData = $input_data['timetableData']; // JS에서 전달된, 각 수업의 '시작' 교시만 포함된 배열

// 수업 지속 시간을 2로 고정
const FIXED_SERVER_CLASS_DURATION = 2; // 서버에서도 동일하게 2시간으로 고정

try {
    $db->beginTransaction();

    // 이 학년의 기존 시간표 데이터를 모두 삭제
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':grade', $gradeToSave);
    $stmt->execute();

    // 새로운 시간표 데이터 삽입
    $insert_stmt = $db->prepare("INSERT INTO user_timetables (user_id, class_id, day, period, grade) VALUES (:user_id, :class_id, :day, :period, :grade)");

    foreach ($timetableData as $item) {
        $class_id = $item['class_id'];
        $day = $item['day'];
        $start_period = $item['period'];
        $grade = $item['grade'];

        // FIXED_SERVER_CLASS_DURATION 만큼 각 교시를 개별 레코드로 삽입
        for ($p = $start_period; $p < $start_period + FIXED_SERVER_CLASS_DURATION; $p++) {
            if ($p > 10) { // 10교시를 넘어가는 수업은 저장하지 않음 (프론트엔드에서 이미 막지만, 백엔드에서도 유효성 검사)
                break;
            }
            $insert_stmt->bindParam(':user_id', $userId);
            $insert_stmt->bindParam(':class_id', $class_id);
            $insert_stmt->bindParam(':day', $day);
            $insert_stmt->bindParam(':period', $p); // 각 교시별로 저장
            $insert_stmt->bindParam(':grade', $grade);
            $insert_stmt->execute();
        }
    }

    $db->commit();
    $response = ['status' => 'success', 'message' => '時間割が正常に保存されました。'];

} catch (PDOException $e) {
    $db->rollBack();
    error_log("時間割保存データベースエラー: " . $e->getMessage());
    if ($e->getCode() === '23000') { // SQLSTATE for Integrity Constraint Violation
        $response['message'] = '保存中に重複または無効なデータが見つかりました。時間割の内容を確認してください。';
    } else {
        $response['message'] = 'データベースエラーが発生しました。管理者にお問い合わせください。';
    }
} catch (Exception $e) {
    $db->rollBack();
    error_log("時間割保存アプリケーションエラー: " . $e->getMessage());
    $response['message'] = 'エラーが発生しました: ' . $e->getMessage();
}

echo json_encode($response);