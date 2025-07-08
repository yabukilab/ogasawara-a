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

// 필요한 데이터 (user_id, timetable 배열, timetable_grade, timetable_term)가 모두 있는지 확인합니다.
if (!isset($data['user_id']) || !isset($data['timetable']) || !isset($data['timetable_grade']) || !isset($data['timetable_term'])) {
    echo json_encode(['status' => 'error', 'message' => '必要なデータが不足しています。']);
    exit();
}

$user_id = $data['user_id'];
$timetable_entries = $data['timetable'];
$timetable_grade = $data['timetable_grade'];     // main_script.js에서 보낸 timetable_grade 값
$timetable_term = $data['timetable_term'];       // 새로 추가된 timetable_term 값

// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。']);
    exit();
}

try {
    $db->beginTransaction(); // 트랜잭션 시작

    // 1. 해당 사용자, '선택된 시간표 학년', 그리고 '선택된 시간표 학기'에 해당하는 기존 시간표 데이터를 모두 삭제합니다.
    // 기존 DELETE 쿼리에 timetable_term 조건을 추가합니다.
    $stmt_delete = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND timetable_grade = :timetable_grade AND timetable_term = :timetable_term");
    $stmt_delete->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_delete->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt_delete->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR); // timetable_term 바인딩 (VARCHAR이므로 PARAM_STR)
    $stmt_delete->execute();

    // 2. 새로운 시간표 데이터를 삽입합니다.
    if (empty($timetable_entries)) {
        $db->commit();
        echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました。(空の時間割)']);
        exit();
    }

    // 삽입 준비 (grade 컬럼과 새로 추가된 timetable_grade, timetable_term 컬럼 모두 포함)
    $sql_insert = "INSERT INTO user_timetables (user_id, timetable_grade, timetable_term, class_id, day, period, grade) VALUES (:user_id, :timetable_grade, :timetable_term, :class_id, :day, :period, :grade)";
    $stmt_insert = $db->prepare($sql_insert);

    // 각 class_id에 해당하는 grade를 미리 조회하여 캐싱할 맵 (성능 최적화)
    $class_grades_cache = [];

    // class_id로 class.grade를 조회하는 쿼리 준비 (수업 자체의 학년)
    $stmt_get_class_grade = $db->prepare("SELECT grade FROM class WHERE id = :class_id");

    foreach ($timetable_entries as $entry) {
        if (!isset($entry['class_id']) || !isset($entry['day_of_week']) || !isset($entry['period'])) {
            $db->rollBack();
            echo json_encode(['status' => 'error', 'message' => '時間割データ形式が無効です。']);
            exit();
        }

        $class_id = $entry['class_id'];
        $day = $entry['day_of_week'];
        $period = $entry['period'];

        // class 테이블에서 해당 class_id의 grade (수업 자체의 학년)를 조회합니다.
        // 캐시 확인
        if (!isset($class_grades_cache[$class_id])) {
            $stmt_get_class_grade->bindParam(':class_id', $class_id, PDO::PARAM_INT);
            $stmt_get_class_grade->execute();
            $class_info = $stmt_get_class_grade->fetch(PDO::FETCH_ASSOC);

            if ($class_info && isset($class_info['grade'])) {
                $class_grades_cache[$class_id] = $class_info['grade'];
            } else {
                // 해당 class_id에 대한 grade를 찾을 수 없는 경우
                $db->rollBack();
                echo json_encode(['status' => 'error', 'message' => '授業IDに該当する学年情報が見つかりません。']);
                exit();
            }
        }
        $class_actual_grade = $class_grades_cache[$class_id]; // 수업 자체의 학년

        $stmt_insert->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT); // 새로 추가된 timetable_grade 바인딩
        $stmt_insert->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);   // <-- 새로 추가된 timetable_term 바인딩
        $stmt_insert->bindParam(':class_id', $class_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':day', $day, PDO::PARAM_STR);
        $stmt_insert->bindParam(':period', $period, PDO::PARAM_INT);
        $stmt_insert->bindParam(':grade', $class_actual_grade, PDO::PARAM_INT); // 수업 자체의 학년 바인딩
        $stmt_insert->execute();
    }

    $db->commit(); // 모든 작업 성공
    echo json_encode(['status' => 'success', 'message' => '時間割が正常に保存されました！']);

} catch (PDOException $e) {
    $db->rollBack(); // 오류 발생 시 롤백
    error_log("Error saving timetable: " . $e->getMessage()); // 에러 로그에 기록
    echo json_encode(['status' => 'error', 'message' => '時間割の保存中にデータベースエラーが発生しました。']);
}
?>