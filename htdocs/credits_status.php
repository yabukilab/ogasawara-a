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
    '教養基礎科目' => 15,
    '教養共通科目' => 20,
    '教養特別科目' => 1,
    '学部共通専門科目' => 20,
    '基礎科目' => 8,
    '基幹科目' => 22,
    '展開科目' => 17,
    '発展科目' => 11,
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


    // カテゴリ1（教養科目・専門科目）別単位数集計
$sqlCat1 = "
    SELECT c.category1, SUM(c.credit) AS earned_credits
    FROM user_timetables ut
    JOIN class c ON ut.class_id = c.id
    WHERE ut.user_id = :user_id
    GROUP BY c.category1
";
$stmt1 = $db->prepare($sqlCat1);
$stmt1->execute([':user_id' => $userId]);
$cat1Results = $stmt1->fetchAll(PDO::FETCH_ASSOC);

$earnedByCat1 = [];
foreach ($cat1Results as $row) {
    $earnedByCat1[$row['category1']] = (int)$row['earned_credits'];
}


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
<div class="container">
    <h1>単位取得状況</h1>

    <div class="credits-summary">
        <p>合計取得単位数：<span id="total-credits"><?= $totalCredits ?></span> 単位</p>
    </div>

    <h2>カテゴリ別取得単位と不足単位</h2>
    <ul id="category-credits-list">
        <?php foreach ($requiredCreditsByCategory2 as $category => $required): 
            $earned = isset($earnedMap[$category]) ? $earnedMap[$category] : 0;
            $shortage = max(0, $required - $earned);
        ?>
        <li>
            <span><?= htmlspecialchars($category) ?></span>
            <span>
                <?= $earned ?> 単位
                <?php if ($shortage > 0): ?>
                    （あと <span class="error-message"><?= $shortage ?> 単位必要</span>）
                <?php else: ?>
                    （<span class="success-message">要件達成</span>）
                <?php endif; ?>
            </span>
        </li>
        <?php endforeach; ?>
    </ul>

    <h2>進級判定</h2>
<div class="message-container">
    <ul>
        <?php
        $gradeStatus = [];

        // カテゴリ2（中分類）から必要値取得
        $core = $earnedMap['基幹科目'] ?? 0;
        $liberal = $earnedMap['教養基礎科目'] ?? 0;
        $specialized = $earnedMap['学部共通専門科目'] ?? 0;

        // カテゴリ1（大分類）から専門科目数取得
        $cat1Specialized = $earnedByCat1['専門科目'] ?? 0;

        // 2年進級
        if ($totalCredits >= 30 && $core >= 15) {
            $gradeStatus[] = "2年進級可能（基幹科目15単位以上，合計30単位以上）";
        }

        // 3年進級
        if ($totalCredits >= 64 && $liberal >= 10 && $cat1Specialized >= 44) {
            $gradeStatus[] = "3年進級可能（教養基礎科目10単位以上，専門科目44単位以上，合計64単位以上）";
        }

        // 4年進級
        if ($totalCredits >= 90 && $specialized >= 10) {
            $gradeStatus[] = "4年進級可能（学部共通専門科目10単位以上，合計90単位以上）";
        }

        // 中分類（category2）すべての要件を満たしているか
$category2Ok = true;
foreach ($requiredCreditsByCategory2 as $cat => $required) {
    $earned = $earnedMap[$cat] ?? 0;
    if ($earned < $required) {
        $category2Ok = false;
        break;
    }
}

// 卒業判定（124単位以上，カテゴリ2すべてOK，専門科目60単位以上）
if ($totalCredits >= 124 && $category2Ok && $cat1Specialized >= 60) {
    $gradeStatus[] = "卒業要件達成（専門科目60単位以上，すべてのカテゴリ2要件達成）";
}


        if (count($gradeStatus) > 0) {
            foreach ($gradeStatus as $status) {
                echo "<li class='success-message' style='margin-bottom: 10px;'>" . htmlspecialchars($status) . "</li>";
            }
        } else {
            echo "<li class='error-message'>まだ進級・卒業要件を満たしていません。</li>";
        }
        ?>
    </ul>
</div>


    <a href="index.php" class="back-button">時間割に戻る</a>
</div>
</body>
</html>
