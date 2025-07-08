<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결 (여기서 $db 객체가 생성됨)

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // 로그인 페이지로 리다이렉트
    exit;
}

$user_id = $_SESSION['user_id'];
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // 게스트 (Guest)
$department = $_SESSION['department'] ?? '';

$timetableData = []; // 시간표 데이터를 저장할 배열
$grades = []; // 사용자가 저장한 시간표의 모든 학년 목록
$terms = []; // 사용자가 저장한 시간표의 모든 학기 목록

// $db 객체가 유효한지 확인
if (!isset($db) || !($db instanceof PDO)) {
    echo '<p style="color: red;">データベース接続エラーが発生しました。管理者に連絡してください。</p>';
    error_log("データベース接続オブジェクト (\$db) が無効です。confirmed_timetable.php");
    exit(); // 스크립트 종료
}

// 사용자가 저장한 시간표의 모든 고유 학년과 학기를 가져옵니다.
try {
    // 모든 학년 가져오기 (timetable_grade 컬럼 사용)
    $stmt_grades = $db->prepare("SELECT DISTINCT timetable_grade FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_grade ASC");
    $stmt_grades->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_grades->execute();
    $grades = $stmt_grades