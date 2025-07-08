<?php
// get_timetable.php

require_once 'db.php'; // db 연결 포함

header('Content-Type: application/json');

session_start();

$user_id = $_SESSION['user_id'] ?? null;
$timetable_grade = $_GET['timetable_grade'] ?? null;
$timetable_term = $_GET['timetable_term'] ?? null;

if ($user_id === null || $timetable_grade === null || $timetable_term === null) {
    echo json_encode(['status' => 'error', 'message' => '必要な情報が不足しています。']);
    exit;
}

try {
    // === 중요: 이 SQL 쿼리를 수정하세요! ===
    // MySQL 8.0+ 또는 MariaDB 10.2+ 인 경우 Window Functions (ROW_NUMBER) 사용
    // 이 방법이 가장 정확하고 성능이 좋습니다.
    $stmt = $db->prepare("
        SELECT
            ut.class_id,
            ut.day,
            ut.period,
            c.name AS class_name,
            c.credit AS class_credit,
            c.grade AS class_original_grade
        FROM (
            SELECT
                *,
                ROW_NUMBER() OVER (PARTITION BY user_id, timetable_grade, timetable_term, day, period ORDER BY id ASC) as rn
            FROM user_timetables
            WHERE user_id = :user_id
              AND timetable_grade = :timetable_grade
              AND timetable_term = :timetable_term
        ) AS ut
        JOIN class AS c ON ut.class_id = c.id
        WHERE ut.rn = 1
        ORDER BY FIELD(ut.day, '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日', '日曜日'), ut.period ASC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);
    $stmt->execute();
    $timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 구버전 MySQL/MariaDB (Window Functions 미지원)을 위한 대체 방안 (성능은 떨어질 수 있음)
    // 이 SQL을 사용할 경우 어떤 class_id가 선택될지 예측하기 어려움 (가장 낮은 id를 가진 class_id를 선택하는 방식)
    /*
    $stmt = $db->prepare("
        SELECT
            t1.class_id,
            t1.day,
            t1.period,
            c.name AS class_name,
            c.credit AS class_credit,
            c.grade AS class_original_grade
        FROM user_timetables t1
        INNER JOIN (
            SELECT
                user_id,
                timetable_grade,
                timetable_term,
                day,
                period,
                MIN(id) as min_id
            FROM user_timetables
            WHERE user_id = :user_id
              AND timetable_grade = :timetable_grade
              AND timetable_term = :timetable_term
            GROUP BY user_id, timetable_grade, timetable_term, day, period
        ) AS t2 ON t1.id = t2.min_id
        JOIN class AS c ON t1.class_id = c.id
        ORDER BY FIELD(t1.day, '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日', '日曜日'), t1.period ASC
    ");
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_grade', $timetable_grade, PDO::PARAM_INT);
    $stmt->bindParam(':timetable_term', $timetable_term, PDO::PARAM_STR);
    $stmt->execute();
    $timetable = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */

    echo json_encode(['status' => 'success', 'timetable' => $timetable]);

} catch (PDOException $e) {
    error_log("Database error in get_timetable.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => '時間割データの取得中にエラーが発生しました。']);
}
?>