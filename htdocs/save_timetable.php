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

// 이 부분의 조건이 "무효한 데이터" 에러를 발생시킵니다.
// main_script.js에서 전송하는 데이터가 이 조건에 맞지 않을 때 발생합니다.
if (!isset($data['timetable']) || !is_array($data['timetable']) || !isset($data['grade']) || !isset($data['term'])) {
    $response['message'] = '無効なデータです。期待されるキー: timetable (配列), grade, term。';
    error_log("Invalid data received in save_timetable.php for user {$user_id}: " . print_r($data, true)); // 상세 로그 추가
    echo json_encode($response);
    exit;
}

$timetable = $data['timetable'];
$timetable_grade = $data['grade'];
$timetable_term = $data['term'];

// $db 객체가 유효한지 확인 (db.php에서 연결 실패 시 대비)
if (!isset($db) || !($db instanceof PDO)) {
    $response['message'] = 'データベース接続オブジェクト ($db) が無効です。管理者に連絡してください。';
    error_log("データベース接続オブジェクト (\$db) が無効です。save_timetable.php");
    echo json_encode($response);
    exit();
}

try {
    // 트랜잭션 시작
    $db->beginTransaction(); // $db 사용

    // 1. 해당 user_id, timetable_grade, timetable_term 에 해당하는 모든 시간표를 삭제
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND timetable_grade = :grade AND timetable_term = :term");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
    $stmt->execute();

    // 2. 새로운 시간표 데이터 삽입
    // 빈 시간표가 저장될 수도 있으므로 foreach 루프는 데이터가 있을 때만 실행
    if (!empty($timetable)) {
        $stmt = $db->prepare("INSERT INTO user_timetables (user_id, timetable_grade, timetable_term, class_id, day, period) VALUES (:user_id, :grade, :term, :class_id, :day, :period)");

        foreach ($timetable as $item) {
            // 필수 키 체크 (클라이언트에서 잘못된 데이터를 보낼 경우 대비)
            if (!isset($item['class_id']) || !isset($item['day']) || !isset($item['period'])) {
                throw new Exception("時間割項目に不足しているキーがあります: " . print_r($item, true));
            }
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':grade', $timetable_grade, PDO::PARAM_INT);
            $stmt->bindParam(':term', $timetable_term, PDO::PARAM_STR);
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
    // DB 관련 오류 발생 시 롤백
    if ($db->inTransaction()) { // 트랜잭션이 활성화된 경우에만 롤백 시도
        $db->rollBack();
    }
    $response['message'] = '時間割の保存中にデータベースエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable DB Error for user {$user_id}: " . $e->getMessage());
} catch (Exception $e) {
    // 일반적인 PHP 코드 오류 발생 시 롤백 (예: throw new Exception)
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    $response['message'] = '時間割の保存中にエラーが発生しました: ' . $e->getMessage();
    error_log("Save Timetable General Error for user {$user_id}: " . $e->getMessage());
}

echo json_encode($response);
?>