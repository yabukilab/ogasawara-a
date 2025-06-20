<?php
session_start();
header('Content-Type: application/json');

$student_number_from_session = $_SESSION['student_number'] ?? null;

if ($student_number_from_session === null) {
    echo json_encode(['success' => false, 'message' => 'ログインしていません。 (Not logged in.)']);
    exit;
}

$grade = isset($_GET['grade']) ? (int)$_GET['grade'] : null;

if ($grade === null || $grade < 1 || $grade > 4) {
    echo json_encode(['success' => false, 'message' => '無効な学年パラメータです。 (Invalid grade parameter.)']);
    exit;
}

$input = file_get_contents('php://input');
$timetableData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'JSONデータ解析エラー: ' . json_last_error_msg()]);
    exit;
}

// 파일 경로에 학년 정보 포함
$filePath = __DIR__ . '/confirmed_timetable_data_student' . $student_number_from_session . '_grade' . $grade . '.json';

$jsonContent = json_encode($timetableData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if ($jsonContent === false) {
    echo json_encode(['success' => false, 'message' => '時間割データのエンコードに失敗しました。']);
    exit;
}

if (file_put_contents($filePath, $jsonContent) === false) {
    echo json_encode(['success' => false, 'message' => '時間割データの保存に失敗しました。']);
    exit;
}

echo json_encode(['success' => true, 'message' => '時間割が正常に保存されました。']);
?>