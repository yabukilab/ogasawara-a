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

    <h2>進級・卒業判定</h2>
<div class="message-container">
    <ul>
        <?php
        $core = $earnedMap['基幹科目'] ?? 0;
        $liberal = $earnedMap['教養基礎科目'] ?? 0;
        $common = $earnedMap['教養共通科目'] ?? 0;
        $special = $earnedMap['教養特別科目'] ?? 0;

        $gakubuCommon = $earnedMap['学部共通専門科目'] ?? 0;
        $foundation = $earnedMap['基礎科目'] ?? 0;
        $develop = $earnedMap['展開科目'] ?? 0;
        $advanced = $earnedMap['発展科目'] ?? 0;

        $cat1Specialized = $earnedByCat1['専門科目'] ?? 0;

        // 2年進級
        if ($totalCredits >= 24) {
            echo "<li class='success-message'>2年進級可能（合計24単位以上）</li>";
        } else {
            echo "<li class='error-message'>2年進級不可：あと " . (24 - $totalCredits) . " 単位必要</li>";
        }

        // 3年進級
        if ($totalCredits >= 64 && $cat1Specialized >= 44) {
            echo "<li class='success-message'>3年進級可能（合計64単位以上，専門科目44単位以上）</li>";
        } else {
            $msg = [];
            if ($totalCredits < 64) $msg[] = "合計あと " . (64 - $totalCredits) . " 単位";
            if ($cat1Specialized < 44) $msg[] = "専門科目あと " . (44 - $cat1Specialized) . " 単位";
            echo "<li class='error-message'>3年進級不可：" . implode("，", $msg) . "</li>";
        }

        // 4年進級
        $fourthOk = (
            $totalCredits >= 102 &&
            $gakubuCommon >= 18 &&
            $foundation >= 8 &&
            $core >= 20 &&
            $develop >= 16 &&
            $advanced >= 6
        );
        if ($fourthOk) {
            echo "<li class='success-message'>4年進級可能（専門5分類クリア，合計102単位以上）</li>";
        } else {
            $msg = [];
            if ($totalCredits < 102) $msg[] = "合計あと " . (102 - $totalCredits) . " 単位";
            if ($gakubuCommon < 18) $msg[] = "学部共通専門科目あと " . (18 - $gakubuCommon) . " 単位";
            if ($foundation < 8) $msg[] = "基礎科目あと " . (8 - $foundation) . " 単位";
            if ($core < 20) $msg[] = "基幹科目あと " . (20 - $core) . " 単位";
            if ($develop < 16) $msg[] = "展開科目あと " . (16 - $develop) . " 単位";
            if ($advanced < 6) $msg[] = "発展科目あと " . (6 - $advanced) . " 単位";
            echo "<li class='error-message'>4年進級不可：" . implode("，", $msg) . "</li>";
        }

        // 卒業判定
        $gradOk = (
            $totalCredits >= 124 &&
            $liberal >= 15 &&
            $common >= 20 &&
            $special >= 1 &&
            $gakubuCommon >= 20 &&
            $foundation >= 8 &&
            $core >= 22 &&
            $develop >= 17 &&
            $advanced >= 11
        );
        if ($gradOk) {
            echo "<li class='success-message'>卒業要件達成（教養・専門すべての条件をクリア）</li>";
        } else {
            $msg = [];
            if ($totalCredits < 124) $msg[] = "合計あと " . (124 - $totalCredits) . " 単位";
            if ($liberal < 15) $msg[] = "教養基礎科目あと " . (15 - $liberal) . " 単位";
            if ($common < 20) $msg[] = "教養共通科目あと " . (20 - $common) . " 単位";
            if ($special < 1) $msg[] = "教養特別科目あと " . (1 - $special) . " 単位";
            if ($gakubuCommon < 20) $msg[] = "学部共通専門科目あと " . (20 - $gakubuCommon) . " 単位";
            if ($foundation < 8) $msg[] = "基礎科目あと " . (8 - $foundation) . " 単位";
            if ($core < 22) $msg[] = "基幹科目あと " . (22 - $core) . " 単位";
            if ($develop < 17) $msg[] = "展開科目あと " . (17 - $develop) . " 単位";
            if ($advanced < 11) $msg[] = "発展科目あと " . (11 - $advanced) . " 単位";
            echo "<li class='error-message'>卒業要件未達：" . implode("，", $msg) . "</li>";
        }
        ?>
    </ul>
</div>




    <a href="index.php" class="back-button">時間割に戻る</a>
</div>
</body>
</html>
