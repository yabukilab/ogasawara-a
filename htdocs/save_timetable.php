<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// 응답 헤더를 JSON 형식으로 설정합니다.
header('Content-Type: application/json');

// POST 요청이 아니면 에러를 반환합니다.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => '無効なリクエストメソッドです。']);
    exit();
}

// JSON 형식의 요청 본문을 가져와 디코딩합니다.
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// 필요한 데이터가 모두 있는지 확인합니다.
// main_script.js는 timetableData에 class_id, day_of_week, period를 담아 보냅니다.
// user_timetables 테이블에 grade 컬럼이 추가되었으므로, main_script.js에서 grade도 함께 보내도록 하거나
// 여기 save_timetable.php에서 해당 class_id의 grade를 다시 조회하여 삽입해야 합니다.
// 현재 main_script.js는 class_id, day_of_week, period만 보내는 것으로 가정합니다.
// 따라서 save_timetable.php에서 class_id를 가지고 class 테이블에서 grade를 조회하는 로직을 추가합니다.
if (!isset($data['user_id']) || !isset($data['timetable'])) {
    echo json_encode(['status' => 'error', 'message' => '必要なデータが不足しています。']);
    exit();
}

$user_id = $data['user_id'];
$timetable_entries = $data['timetable'];

// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。']);
    exit();
}

try {
    $db->beginTransaction(); // 트랜잭션 시작

    // 1. 해당 사용자의 기존 시간표 데이터를 모두 삭제합니다.
    $stmt_delete = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id");
    $stmt_delete->bindParam(':user_id', $user_id);
    $stmt_delete->execute();

    // 2. 새로운 시간표 데이터를 삽입합니다.
    if (empty($timetable_entries)) {
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました。(空の時間割)']);
        exit();
    }

    // 삽입 준비 (grade 컬럼 추가)
    $sql_insert = "INSERT INTO user_timetables (user_id, class_id, day, period, grade) VALUES (:user_id, :class_id, :day, :period, :grade)";
    $stmt_insert = $db->prepare($sql_insert);

    // 각 class_id에 해당하는 grade를 미리 조회하여 캐싱할 맵 (성능 최적화)
    $class_grades_cache = [];

    // class_id로 grade를 조회하는 쿼리 준비
    $stmt_get_grade = $db->prepare("SELECT grade FROM class WHERE id = :class_id");


    foreach ($timetable_entries as $entry) {
        if (!isset($entry['class_id']) || !isset($entry['day_of_week']) || !isset($entry['period'])) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => '時間割データ形式が無効です。']);
            exit();
        }

        $class_id = $entry['class_id'];
        $day = $entry['day_of_week']; // main_script.js에서 보내는 이름에 맞춤
        $period = $entry['period'];   // main_script.js에서 보내는 이름에 맞춤

        // class 테이블에서 해당 class_id의 grade를 조회합니다.
        // 캐시 확인
        if (!isset($class_grades_cache[$class_id])) {
            $stmt_get_grade->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt_get_grade->execute();
            $class_info = $stmt_get_grade->fetch(PDO::FETCH_ASSOC);

            if ($class_info && isset($class_info['grade'])) {
                $class_grades_cache[$class_id] = $class_info['grade'];
            } else {
                // 해당 class_id에 대한 grade를 찾을 수 없는 경우
                $db->rollBack();
                echo json_encode(['status' => 'error', 'message' => '授業IDに該当する学年情報が見つかりません。']);
                exit();
            }
        }
        $grade = $class_grades_cache[$class_id];

        $stmt_insert->bindParam(':user_id', $user_id);
        $stmt_insert->bindParam(':class_id', $class_id);
        $stmt_insert->bindParam(':day', $day); // user_timetables의 'day' 컬럼에 바인딩
        $stmt_insert->bindParam(':period', $period); // user_timetables의 'period' 컬럼에 바인딩
        $stmt_insert->bindParam(':grade', $grade); // user_timetables의 'grade' 컬럼에 바인딩
        $stmt_insert->execute();
    }

    $db->commit(); // 모든 작업 성공
    echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました！']);

} catch (PDOException $e) {
    $db->rollBack(); // 오류 발생 시 롤백
    error_log("Error saving timetable: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => '時間割の保存中にデータベースエラーが発生しました。']);
}
?>