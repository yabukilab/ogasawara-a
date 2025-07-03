<?php
session_start();
require_once 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>進級・卒業判定</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f9f9f9; }
        h1 { color: #333; }
        .result { margin-top: 20px; padding: 15px; background: #fff; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        ul { margin-top: 10px; }
        li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <h1>進級・卒業判定</h1>
    <div class="result">
<?php
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color: red;'>ユーザーがログインしていません。</p>";
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // 履修科目（重複除外）とその単位数を取得
    $sql = "
        SELECT DISTINCT class_id, credit
        FROM user_timetables ut
        JOIN class c ON ut.class_id = class_id
        WHERE ut.user_id = :user_id
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCredits = 0;
    foreach ($classes as $class) {
        $totalCredits += (int)$class['credit'];
    }

    // 判定結果作成
    $gradeStatus = [];
    if ($totalCredits >= 30) $gradeStatus[] = "2年進級可能";
    if ($totalCredits >= 60) $gradeStatus[] = "3年進級可能";
    if ($totalCredits >= 90) $gradeStatus[] = "4年進級可能";
    if ($totalCredits >= 120) $gradeStatus[] = "卒業要件達成";

    echo "<p>合計取得単位数：<strong>{$totalCredits}</strong> 単位</p>";
    echo "<p>判定結果：</p><ul>";
    if (count($gradeStatus) > 0) {
        foreach ($gradeStatus as $status) {
            echo "<li>{$status}</li>";
        }
    } else {
        echo "<li>まだ進級・卒業要件を満たしていません。</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    error_log("進級判定エラー: " . $e->getMessage());

    // 詳細なエラーをブラウザに表示（開発中のみ推奨）
    echo "<p style='color: red;'>データベースエラーが発生しました：</p>";
    echo "<pre style='color: red; background: #eee; padding: 10px; border-radius: 6px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}

?>
    </div>
</body>
</html>
