<?php
session_start();
require_once 'db.php'; // データベース接続 (後述のdb.phpを参照)

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
        strong.red { color: red; }
        strong.green { color: green; }
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
    // -----------------------------
    // 1. 合計取得単位数の計算
    // -----------------------------
    $stmt = $db->prepare("
        SELECT DISTINCT ut.class_id, c.credit
        FROM user_timetables ut
        JOIN class c ON ut.class_id = c.id
        WHERE ut.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $userId]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalCredits = 0;
    foreach ($classes as $class) {
        $totalCredits += (int)$class['credit'];
    }

    // -----------------------------
    // 2. 進級・卒業判定
    // -----------------------------
    $gradeStatus = [];
    if ($totalCredits >= 120) {
        $gradeStatus[] = "卒業要件達成";
    } else {
        if ($totalCredits >= 90) $gradeStatus[] = "4年進級可能";
        if ($totalCredits >= 60) $gradeStatus[] = "3年進級可能";
        if ($totalCredits >= 30) $gradeStatus[] = "2年進級可能";
    }

    // -----------------------------
    // 3. カテゴリ別 単位取得と不足単位
    // -----------------------------
    $requiredCreditsByCategory = [
        "基幹" => 30,
        "専門" => 60,
        "一般" => 30
    ];

    $stmt = $db->prepare("
        SELECT c.category1, SUM(c.credit) AS total
        FROM user_timetables ut
        JOIN class c ON ut.class_id = c.id
        WHERE ut.user_id = :user_id
        GROUP BY c.category1
    ");
    $stmt->execute([':user_id' => $userId]);
    $categoryCredits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [カテゴリ => 単位]

    // 不足単位計算
    $missingByCategory = [];
    foreach ($requiredCreditsByCategory as $category => $required) {
        $earned = isset($categoryCredits[$category]) ? $categoryCredits[$category] : 0;
        $missing = max(0, $required - $earned);
        $missingByCategory[$category] = [
            '取得済み' => $earned,
            '不足' => $missing
        ];
    }

    // -----------------------------
    // 表示
    // -----------------------------
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

    echo "<h3>カテゴリ別 単位取得状況</h3><ul>";
    foreach ($missingByCategory as $category => $data) {
        echo "<li>{$category}：{$data['取得済み']} / {$requiredCreditsByCategory[$category]} 単位";
        if ($data['不足'] > 0) {
            echo "（あと <strong class='red'>{$data['不足']}</strong> 単位必要）";
        } else {
            echo "（<strong class='green'>要件達成</strong>）";
        }
        echo "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>データベースエラーが発生しました：</p>";
    echo "<pre style='color: red; background: #eee; padding: 10px; border-radius: 6px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
?>
    </div>
    <div style="margin-top: 30px;">
        <a href="index.php" style="display: inline-block; padding: 10px 20px; background-color: #4da6ff; color: white; text-decoration: none; border-radius: 6px;">
            時間割に戻る
        </a>
    </div>
</body>
</html>
