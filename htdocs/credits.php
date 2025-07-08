<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 登録済み時間割から科目情報を取得し、分類別に集計
$sql = "
SELECT 
    s.category1,
    s.category2,
    s.category3,
    SUM(s.credit) AS total_credits
FROM (
    SELECT DISTINCT subject_id
    FROM timetables
    WHERE user_id = ?
) AS unique_subjects
JOIN subjects s ON unique_subjects.subject_id = s.id
GROUP BY s.category1, s.category2, s.category3
ORDER BY s.category1, s.category2, s.category3
";

$stmt = $db->prepare($sql);
$stmt->execute([$user_id]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>取得単位確認</title>
    <link rel="stylesheet" href="css/credits.css">
</head>
<body>
    <div class="container">
        <h1>取得済単位の確認</h1>
        <table>
            <thead>
                <tr>
                    <th>大分類 (category1)</th>
                    <th>中分類 (category2)</th>
                    <th>小分類 (category3)</th>
                    <th>取得単位数</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                    <tr><td colspan="4">取得単位はまだありません。</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['category1']) ?></td>
                            <td><?= htmlspecialchars($row['category2']) ?></td>
                            <td><?= htmlspecialchars($row['category3']) ?></td>
                            <td><?= htmlspecialchars($row['total_credits']) ?> 単位</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="buttons">
            <a href="shortage.php" class="btn blue">不足単位を確認する</a>
            <a href="menu.php" class="btn green">メニューに戻る</a>
        </div>
    </div>
    <div class="bottom-nav">
        <a href="menu.php" class="nav-button">メニュー</a>
        <a href="timetable_register.php" class="nav-button">時間割登録</a>
        <a href="timetable_confirm.php" class="nav-button">時間割確認</a>
        <a href="shortage.php" class="nav-button">不足単位確認</a>
    </div>
</body>
</html>
