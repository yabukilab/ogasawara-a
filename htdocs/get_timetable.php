<?php
session_start();
require_once 'db_config.php'; // DB 설정 파일 포함

header('Content-Type: application/json'); // JSON 응답을 위한 헤더 설정

$response = ['status' => 'error', 'message' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'User not logged in.';
    echo json_encode($response);
    exit();
}

$userId = $_SESSION['user_id'];
$grade = $_GET['grade'] ?? 1; // 어떤 학년의 시간표를 가져올지 (필터링된 학년)

try {
    // user_timetable 테이블에서 해당 user_id와 grade에 맞는 시간표 데이터를 가져옴
    // class_id를 이용해 class 테이블에서 수업명, 학점, 학기 등도 조인하여 가져오는 것이 일반적
    $stmt = $db->prepare("
        SELECT
            ut.day, ut.period, ut.class_id,
            c.name as className, c.credit as classCredit, c.term as classTerm, c.grade as classGrade
        FROM
            user_timetable ut
        JOIN
            class c ON ut.class_id = c.id
        WHERE
            ut.user_id = :user_id AND ut.grade = :grade
        ORDER BY
            ut.day, ut.period
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':grade', $grade); // 특정 학년의 시간표만 불러오는 경우
    $stmt->execute();
    $timetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response['status'] = 'success';
    $response['data'] = $timetableData;

} catch (PDOException $e) {
    error_log("Get Timetable DB Error: " . $e->getMessage());
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>