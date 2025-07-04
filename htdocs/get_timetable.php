<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// 응답 헤더를 JSON 형식으로 설정합니다.
header('Content-Type: application/json');

// GET 요청에서 user_id와 timetable_grade 파라미터를 가져옵니다.
$user_id = $_GET['user_id'] ?? null;
$timetable_grade = $_GET['timetable_grade'] ?? null; // <-- timetable_grade 파라미터 추가

// user_id 또는 timetable_grade가 없거나 유효하지 않으면 에러를 반환합니다.
if ($user_id === null || !is_numeric($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーIDが無効です。']);
    exit();
}

// timetable_grade가 없거나 유효하지 않으면 에러를 반환합니다.
// 학년은 1~4학년 또는 다른 유효한 범위로 제한할 수 있습니다. 여기서는 단순히 숫자인지 확인합니다.
if ($timetable_grade === null || !is_numeric($timetable_grade)) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学年情報が無効です。']);
    exit();
}

// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。']);
    exit();
}

try {
    // user_timetables 테이블과 class 테이블을 조인하여 시간표 항목과 수업 상세 정보를 함께 조회합니다.
    // WHERE 절에 timetable_grade 조건을 추가합니다.
    $sql = "SELECT 
                ut.class_id, 
                ut.day,        -- user_timetables의 'day' 컬럼
                ut.period,     -- user_timetables의 'period' 컬럼
                ut.grade AS class_original_grade, -- user_timetables의 'grade' 컬럼 (수업 자체의 학년)
                c.name AS class_name, 
                c.credit AS class_credit,
                c.category1,   -- class 테이블의 'category1' 컬럼
                c.category2,   -- class 테이블의 'category2' 컬럼
                c.category3    -- class 테이블의 'category3' 컬럼
            FROM 
                user_timetables ut
            JOIN 
                class c ON ut.class_id = c.id
            WHERE 
                ut.user_id = :user_id AND ut.timetable_grade = :timetable_grade"; // <-- timetable_grade 조건 추가

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT); // <-- timetable_grade 바인딩
    $stmt->execute();
    
    $timetable_data_for_js = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $timetable_data_for_js[] = [
            'class_id' => $row['class_id'],
            'day' => $row['day'], // main_script.js의 loadTimetable 함수에서 entry.day로 사용됩니다.
            'period' => $row['period'],
            'class_original_grade' => $row['class_original_grade'], // 수업 자체의 학년 정보
            'class_name' => $row['class_name'],
            'class_credit' => $row['class_credit'],
            'category1' => $row['category1'],
            'category2' => $row['category2'],
            'category3' => $row['category3']
        ];
    }

    echo json_encode(['status' => 'success', 'timetable' => $timetable_data_for_js]);

} catch (PDOException $e) {
    // 데이터베이스 오류 발생 시
    error_log("Error loading timetable: " . $e->getMessage()); // 에러 로그 기록
    echo json_encode(['status' => 'error', 'message' => '時間割の読み込み中にデータベースエラーが発生しました。']);
}
?>