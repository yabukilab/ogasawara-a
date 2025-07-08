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
            if ($totalCredits >= 30) $gradeStatus[] = "2年進級可能";
            if ($totalCredits >= 60) $gradeStatus[] = "3年進級可能";
            if ($totalCredits >= 90) $gradeStatus[] = "4年進級可能";
            if ($totalCredits >= 120) $gradeStatus[] = "卒業要件達成";

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
