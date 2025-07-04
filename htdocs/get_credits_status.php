<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결

// 응답 헤더를 JSON 형식으로 설정
header('Content-Type: application/json');

// GET 요청에서 user_id 파라미터를 가져옴
$user_id = $_GET['user_id'] ?? null;

// user_id가 없거나 유효하지 않으면 에러 반환
if ($user_id === null || !is_numeric($user_id)) {
    echo json_encode(['status' => 'error', 'message' => 'ユーザーIDが無効です。']);
    exit();
}

// 현재 로그인된 사용자의 ID와 요청된 user_id가 일치하는지 확인 (보안 강화)
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $user_id) {
    echo json_encode(['status' => 'error', 'message' => '認証情報が無効です。再ログインしてください。']);
    exit();
}

try {
    // user_timetables 테이블과 class 테이블을 조인하여 사용자 시간표의 모든 수업 학점과 카테고리1 정보를 가져옴
    $sql = "SELECT 
                c.credit, 
                c.category1 
            FROM 
                user_timetables ut
            JOIN 
                class c ON ut.class_id = c.id
            WHERE 
                ut.user_id = :user_id";

    $stmt = $db->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $enrolled_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_credits = 0;
    $category_credits = [];

    // 조회된 수업들을 순회하며 학점 계산
    foreach ($enrolled_classes as $class) {
        $credit = (int)$class['credit']; // 학점을 정수로 변환
        $category1 = $class['category1'];

        $total_credits += $credit; // 총 학점 합산

        // 카테고리1별 학점 합산
        if (!isset($category_credits[$category1])) {
            $category_credits[$category1] = 0;
        }
        $category_credits[$category1] += $credit;
    }

    // 결과를 JSON으로 인코딩하여 출력
    echo json_encode([
        'status' => 'success',
        'total_credits' => $total_credits,
        'category_credits' => $category_credits
    ]);

} catch (PDOException $e) {
    // 데이터베이스 오류 발생 시
    error_log("Error getting credits status: " . $e->getMessage()); // 에러 로그 기록
    echo json_encode(['status' => 'error', 'message' => '単位取得状況の取得中にデータベースエラーが発生しました。']);
}
?>