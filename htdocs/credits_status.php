<?php
session_start();
require_once 'db.php'; // $db = PDOオブジェクトが入っている前提

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>ユーザーがログインしていません。</p>";
    exit();
}

$userId = $_SESSION['user_id'];

$requiredCreditsByCategory2 = [
    '基幹科目' => 30,
    '教養基礎科目' => 20,
    '学部共通専門科目' => 25,
];

try {
    $sql = "
        SELECT c.category2, SUM(c.credit) AS earned_credits
        FROM user_timetables ut
        JOIN class c ON ut.class_id = c.id
        WHERE ut.user_id = :user_id
        GROUP BY c.category2
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $earnedMap = [];
    foreach ($results as $row) {
        $earnedMap[$row['category2']] = (int)$row['earned_credits'];
    }

    $totalCredits = array_sum($earnedMap);

} catch (PDOException $e) {
    echo "<p style='color:red;'>データベースエラーが発生しました。</p>";
    exit();
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>単位取得状況</title>
<link rel="stylesheet" href="credits_status.css">
</head>
<body>
    <h1>単位取得状況</h1>
    <div class="result">
        <p>合計取得単位数：<strong><?= $totalCredits ?></strong> 単位</p>

        <p>カテゴリ別取得単位と不足単位：</p>
        <ul>
        <?php foreach ($requiredCreditsByCategory2 as $category => $required): 
            $earned = isset($earnedMap[$category]) ? $earnedMap[$category] : 0;
            $shortage = max(0, $required - $earned);
        ?>
            <li>
                <?= htmlspecialchars($category) ?>：
                <?= $earned ?> 単位
                <?php if ($shortage > 0): ?>
                    （あと <span class="shortage"><?= $shortage ?></span> 単位必要）
                <?php else: ?>
                    （<span class="ok">要件達成</span>）
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ul>

        <p>進級判定：</p>
        <ul>
            <?php
            $gradeStatus = [];
            if ($totalCredits >= 30) $gradeStatus[] = "2年進級可能";
            if ($totalCredits >= 60) $gradeStatus[] = "3年進級可能";
            if ($totalCredits >= 90) $gradeStatus[] = "4年進級可能";
            if ($totalCredits >= 120) $gradeStatus[] = "卒業要件達成";

            if (count($gradeStatus) > 0) {
                foreach ($gradeStatus as $status) {
                    echo "<li>" . htmlspecialchars($status) . "</li>";
                }
            } else {
                echo "<li>まだ進級・卒業要件を満たしていません。</li>";
            }
            ?>
        </ul>

        <a href="index.php" class="btn-back">時間割に戻る</a>
    </div>
</body>
</html>
