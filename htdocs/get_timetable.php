<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// PHP 에러를 화면에 표시 (개발 환경에서만 사용, 실제 서비스에서는 비활성화하거나 로그 파일에만 기록)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 응답 헤더를 JSON 형식으로 설정합니다. UTF-8 인코딩을 명시합니다.
header('Content-Type: application/json; charset=UTF-8');

// GET 요청에서 user_id, timetable_grade, timetable_term 파라미터를 가져옵니다.
$user_id = $_GET['user_id'] ?? null;
$timetable_grade = $_GET['timetable_grade'] ?? null;
$timetable_term = $_GET['timetable_term'] ?? null;

// user_id, timetable_grade, timetable_term이 없거나 유효하지 않으면 에러를 반환합니다.
if ($user_id === null || !is_numeric($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーIDが無効です。'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($timetable_grade === null || !is_numeric($timetable_grade)) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学年情報が無効です。'], JSON_UNESCAPED_UNICODE);
    exit();
}

if ($timetable_term === null || !in_array($timetable_term, ['前期', '後期'])) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学期情報が無効です。'], JSON_UNESCAPED_UNICODE);
    exit();
}

// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // SQL 쿼리 변경: 서브쿼리를 사용하여 각 (day, period) 조합에 대해 가장 최근에 추가된 (id가 가장 큰) 항목을 찾습니다.
    // 이 방식은 MySQL의 ONLY_FULL_GROUP_BY 모드나 다른 DBMS에서 발생할 수 있는 문제를 회피합니다.
    $sql = "SELECT 
                ut.class_id, 
                ut.day,       
                ut.period,    
                ut.grade AS class_original_grade,
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
                AND ut.timetable_term = :timetable_term
                AND ut.id IN (
                    SELECT MAX(id)
                    FROM user_timetables
                    WHERE user_id = :user_id_sub  -- 서브쿼리에도 동일한 user_id 바인딩
                      AND timetable_grade = :timetable_grade_sub -- 서브쿼리에도 동일한 timetable_grade 바인딩
                      AND timetable_term = :timetable_term_sub -- 서브쿼리에도 동일한 timetable_term 바인딩
                    GROUP BY day, period
                )";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);
    // 서브쿼리에도 동일한 파라미터를 바인딩합니다.
    $stmt->bindParam(':user_id_sub', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade_sub', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term_sub', $timetable_term, PDO::PARAM_STR);

    // 디버그를 위해 최종 SQL 쿼리와 파라미터를 로그에 기록합니다.
    error_log("DEBUG get_timetable.php: SQL Query: " . $sql);
    error_log("DEBUG get_timetable.php: Params: " . print_r([
        ':user_id' => $user_id,
        ':timetable_grade' => $timetable_grade,
        ':timetable_term' => $timetable_term
    ], true));

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

    echo json_encode(['status' => 'success', 'timetable' => $timetable_data_for_js], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // 데이터베이스 오류 발생 시
    error_log("Error loading timetable (get_timetable.php): " . $e->getMessage()); // 에러 로그 기록
    echo json_encode(['status' => 'error', 'message' => '時間割の読み込み中にデータベースエラーが発生しました。'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // 기타 예기치 않은 오류 발생 시
    error_log("Unexpected error in get_timetable.php: " . $e->getMessage()); // 에러 로그 기록
    echo json_encode(['status' => 'error', 'message' => '時間割の読み込み中に予期せぬエラーが発生しました。'], JSON_UNESCAPED_UNICODE);
}
?>