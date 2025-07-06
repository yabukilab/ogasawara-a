<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// 응답 헤더를 JSON 형식으로 설정합니다.
header('Content-Type: application/json');

// GET 요청에서 user_id, timetable_grade, timetable_term 파라미터를 가져옵니다.
$user_id = $_GET['user_id'] ?? null;
$timetable_grade = $_GET['timetable_grade'] ?? null;
$timetable_term = $_GET['timetable_term'] ?? null;

// user_id, timetable_grade, timetable_term이 없거나 유효하지 않으면 에러를 반환합니다.
if ($user_id === null || !is_numeric($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーIDが無効です。']);
    exit();
}

if ($timetable_grade === null || !is_numeric($timetable_grade)) {
    echo json_encode(['status' => 'error', 'message' => '時間割の学年情報が無効です。']);
    exit();
}

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
    // 각 (day, period) 조합에 대해 하나의 레코드만 선택하도록 수정합니다.
    // 여기서는 가장 큰 id (가장 최근에 저장된 것으로 가정)를 가진 레코드를 선택합니다.
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
            GROUP BY 
                ut.day, ut.period
            HAVING 
                ut.id = MAX(ut.id)"; // 각 (day, period) 그룹에서 ID가 가장 큰 레코드만 선택

    // --- 다른 방법 (DBMS가 지원한다면 더 명확합니다) ---
    // 만약 사용하는 DBMS가 MySQL 8.0 이상 또는 PostgreSQL, SQLite 최신 버전이라면,
    // ROW_NUMBER()를 사용하는 것이 더 강력하고 명시적입니다.
    /*
    $sql = "SELECT
                class_id, day, period, class_original_grade, class_name, class_credit, category1, category2, category3
            FROM (
                SELECT
                    ut.class_id,
                    ut.day,
                    ut.period,
                    ut.grade AS class_original_grade,
                    c.name AS class_name,
                    c.credit AS class_credit,
                    c.category1,
                    c.category2,
                    c.category3,
                    ROW_NUMBER() OVER(PARTITION BY ut.day, ut.period ORDER BY ut.id DESC) as rn
                FROM
                    user_timetables ut
                JOIN
                    class c ON ut.class_id = c.id
                WHERE
                    ut.user_id = :user_id
                    AND ut.timetable_grade = :timetable_grade
                    AND ut.timetable_term = :timetable_term
            ) AS subquery
            WHERE rn = 1";
    */
    // 현재 `MAX(ut.id)` 방식이 대부분의 MySQL/SQLite 버전에서 작동하며 간단합니다.

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);
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