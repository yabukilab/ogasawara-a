<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// 응답 헤더를 JSON 형식으로 설정합니다.
header('Content-Type: application/json');

// GET 요청에서 user_id, timetable_grade, timetable_term 파라미터를 가져옵니다.
$user_id = $_GET['user_id'] ?? null;
$timetable_grade = $_GET['timetable_grade'] ?? null;
$timetable_term = $_GET['timetable_term'] ?? null; // <-- timetable_term 파라미터 추가

// user_id, timetable_grade, timetable_term이 없거나 유효하지 않으면 에러를 반환합니다.
if ($user_id === null || !is_numeric($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーIDが無効です。']);
    exit();
}

if ($timetable_grade === null || !is_numeric($timetable_grade)) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学年情報が無効です。']);
    exit();
}

// timetable_term은 '前期' 또는 '後期' 문자열이므로, 빈 값인지 아닌지만 확인하거나 특정 값만 허용하도록 할 수 있습니다.
if ($timetable_term === null || !in_array($timetable_term, ['前期', '後期'])) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学期情報が無効です。']);
    exit();
}


// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。']);
    exit();
}

try {
    // user_timetables 테이블과 class 테이블을 조인하여 시간표 항목과 수업 상세 정보를 함께 조회합니다.
    // WHERE 절에 user_id, timetable_grade, timetable_term 조건을 모두 추가합니다.
    $sql = "SELECT 
                ut.class_id, 
                ut.day,        
                ut.period,     
                ut.grade AS class_original_grade, -- user_timetables의 'grade' 컬럼 (수업 자체의 학년)
                c.name AS class_name, 
                c.credit AS class_credit,
                c.category1,   
                c.category2,   
                c.category3    
            FROM 
                user_timetables ut
            JOIN 
                class c ON ut.class_id = c.id
            WHERE 
                ut.user_id = :user_id 
                AND ut.timetable_grade = :timetable_grade 
                AND ut.timetable_term = :timetable_term"; // <-- timetable_term 조건 추가

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR); // <-- timetable_term 바인딩 (VARCHAR이므로 PARAM_STR)
    $stmt->execute();
    
    $timetable_data_for_js = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $timetable_data_for_js[] = [
            'class_id' => $row['class_id'],
            'day' => $row['day'],
            'period' => $row['period'],
            'class_original_grade' => $row['class_original_grade'],
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