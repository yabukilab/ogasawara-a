<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode($_POST['data'], true);
$grade = $_POST['grade'] ?? 1;
$term = $_POST['term'] ?? '前期';

if (!is_array($data)) {
    echo '不正なデータ形式です';
    exit;
}

// 時間割の重複を防ぐため、先に指定の学年・学期の既存データを取得
$stmt = $db->prepare("SELECT day, period FROM timetables WHERE user_id = ? AND grade = ? AND term = ?");
$stmt->execute([$user_id, $grade, $term]);
$existingEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
$existingMap = [];
foreach ($existingEntries as $e) {
    $existingMap[$e['day'] . '-' . $e['period']] = true;
}

// 時間割更新処理
foreach ($data as $entry) {
    $day = (int)$entry['day'];
    $period = (int)$entry['period'];
    $subject_id = $entry['subject_id'] ?? null;
    $key = $day . '-' . $period;

    if ($subject_id) {
        if (isset($existingMap[$key])) {
            // 上書き
            $update = $db->prepare("UPDATE timetables SET subject_id = ? WHERE user_id = ? AND grade = ? AND term = ? AND day = ? AND period = ?");
            $update->execute([$subject_id, $user_id, $grade, $term, $day, $period]);
        } else {
            // 新規挿入
            $insert = $db->prepare("INSERT INTO timetables (user_id, subject_id, day, period, grade, term) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute([$user_id, $subject_id, $day, $period, $grade, $term]);
        }
    } else {
        // 科目が設定されていない → 削除
        if (isset($existingMap[$key])) {
            $delete = $db->prepare("DELETE FROM timetables WHERE user_id = ? AND grade = ? AND term = ? AND day = ? AND period = ?");
            $delete->execute([$user_id, $grade, $term, $day, $period]);
        }
    }
}

// 確認画面にリダイレクト
header("Location: timetable_confirm.php?grade=$grade&term=" . urlencode($term));
exit;
