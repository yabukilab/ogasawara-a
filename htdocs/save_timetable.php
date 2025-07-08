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
    $response['message'] = '無効なデータです。期待されるキー: timetable (配列), grade, term。';
    error_log("Invalid data received in save_timetable.php for user {$user_id}: " . print_r($data, true)); // 상세 로그 추가
    echo json_encode($response);
    exit;
}

$timetable = $data['timetable'];
$timetable_grade = $data['grade']; // JavaScript에서 'grade'로 보냄
$timetable_term = $data['term'];   // JavaScript에서 'term'으로 보냄

// $db 객체가 유효한지 확인 (db.php에서 연결 실패 시 대비)
if (!isset($db) || !($db instanceof PDO)) {
    $response['message'] = 'データベース接続オブジェクト ($db) が無効です。管理者に連絡してください。';
    error_log("データベース接続オブジェクト (\$db) が無効です。save_timetable.php");
    echo json_encode($response);
    exit();
}

try {
    // 트랜잭션 시작
    $db->beginTransaction();

    // 1. 해당 user_id, timetable_grade, timetable_term 에 해당하는 모든 시간표를 삭제
    // 여기서 'grade' 대신 'timetable_grade'를 사용합니다.
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND timetable_grade = :timetable_grade AND timetable_term = :timetable_term");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT); // 변수 이름은 동일하게 바인딩
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);    // 변수 이름은 동일하게 바인딩
    $stmt->execute();

    // 2. 새로운 시간표 데이터 삽입
    if (!empty($timetable)) {
        // 여기서 'grade' 대신 'timetable_grade'를 사용합니다.
        $stmt = $db->prepare("INSERT INTO user_timetables (user_id, timetable_grade, timetable_term, class_id, day, period) VALUES (:user_id, :timetable_grade, :timetable_term, :class_id, :day, :period)");

        foreach ($timetable as $item) {
            if (!isset($item['class_id']) || !isset($item['day']) || !isset($item['period'])) {
                throw new Exception("時間割項目に不足しているキーがあります: " . print_r($item, true));
            }
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT); // 변수 이름은 동일하게 바인딩
            $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);    // 변수 이름은 동일하게 바인딩
            $stmt->bindParam(':class_id', $item['class_id'], PDO::PARAM_INT);
            $stmt->bindParam(':day', $item['day'], PDO::PARAM_STR);
            $stmt->bindParam(':period', $item['period'], PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    // 트랜잭션 커밋
    $db->commit();
    $response['success'] = true;
    $response['message'] = '時間割が正常に保存されました。';

} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = '時間割の保存中にデータベースエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable DB Error for user {$user_id}: " . $e->getMessage());
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = '時間割の保存中にエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable General Error for user {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>