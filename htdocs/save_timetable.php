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

$timetableData = $input_data['timetableData'];

try {
    $db->beginTransaction();

    // 이 학년의 기존 시간표 데이터를 모두 삭제
    // user_timetables 테이블의 Primary Key가 (user_id, day, period, grade) 이므로,
    // 해당 user_id와 grade에 대한 모든 시간표 항목을 초기화합니다.
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':grade', $gradeToSave);
    $stmt->execute();

    // 새로운 시간표 데이터 삽입
    $insert_stmt = $db->prepare("INSERT INTO user_timetables (user_id, class_id, day, period, grade) VALUES (:user_id, :class_id, :day, :period, :grade)");

    foreach ($timetableData as $item) {
        // 삽입하려는 class_id가 classes 테이블에 존재하는지 확인 (FOREIGN KEY가 적용되어 있다면 DB가 자동으로 검사)
        // class_id가 존재하지 않거나, 기타 제약 조건 위반 시 PDOException 발생
        $insert_stmt->bindParam(':user_id', $userId);
        $insert_stmt->bindParam(':class_id', $item['class_id']);
        $insert_stmt->bindParam(':day', $item['day']);
        $insert_stmt->bindParam(':period', $item['period']);
        $insert_stmt->bindParam(':grade', $item['grade']); // 여기서는 전달받은 item['grade'] 사용
        $insert_stmt->execute();
    }

    $db->commit();
    $response = ['status' => 'success', 'message' => '時間割が正常に保存されました。'];

} catch (PDOException $e) {
    $db->rollBack();
    error_log("時間割保存データベースエラー: " . $e->getMessage());
    if ($e->getCode() === '23000') { // SQLSTATE for Integrity Constraint Violation (Duplicate entry, Foreign Key violation)
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