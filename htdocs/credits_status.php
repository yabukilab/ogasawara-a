<?php
session_start();
require_once 'db.php'; // DB接続とh()関数

$loggedIn = isset($_SESSION['user_id']);
$student_number = $_SESSION['student_number'] ?? 'ゲスト';
$department = $_SESSION['department'] ?? '';
$user_id = $_SESSION['user_id'] ?? null;

$categoryCredits = [];
$totalCredits = 0;
$judgement_message = '判定情報を取得できませんでした．';

if ($loggedIn && $user_id) {
    try {
        // 総取得単位
        $stmt = $db->prepare("
            SELECT SUM(c.credit) AS total_credits
            FROM user_timetables ut
            JOIN classes c ON ut.class_id = c.id
            WHERE ut.user_id = ?
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $totalCredits = (int)($result['total_credits'] ?? 0);

        // 分類ごとの単位集計（category1）
        $stmt = $db->prepare("
            SELECT c.category1, SUM(c.credit) AS category_total
            FROM user_timetables ut
            JOIN classes c ON ut.class_id = c.id
            WHERE ut.user_id = ?
            GROUP BY c.category1
        ");
        $stmt->execute([$user_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $categoryCredits[$row['category1']] = (int)$row['category_total'];
        }

        // 判定メッセージ
        if ($totalCredits >= 124) {
            $judgement_message = "🎓 現在の取得単位数は {$totalCredits} 単位です．卒業可能です！";
        } elseif ($totalCredits >= 30) {
            $remaining = 124 - $totalCredits;
            $judgement_message = "✅ 現在の取得単位数は {$totalCredits} 単位です．進級可能ですが，卒業にはあと {$remaining} 単位必要です．";
        } else {
            $needed = 30 - $totalCredits;
            $judgement_message = "⚠️ 現在の取得単位数は {$totalCredits} 単位です．進級にはあと {$needed} 単位必要です．";
        }

    } catch (PDOException $e) {
        $judgement_message = 'データベースエラーが発生しました．';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>単位取得状況 (Credit Status)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="credits_status.css">
</head>
<body>
    <div class="container">
        <div class="user-info">
            <?php if ($loggedIn): ?>
                <p>ようこそ、<?php echo h($student_number); ?> (<?php echo h($department); ?>) さん！
                    <a href="logout.php">ログアウト</a>
                </p>
            <?php else: ?>
                <p>ログインしていません。
                    <a href="login.php">ログイン</a> | 
                    <a href="register_user.php">新規ユーザー登録</a>
                </p>
            <?php endif; ?>
        </div>

        <h1>単位取得状況</h1>

        <div id="credits-status-message" class="message-container">
            <p><?php echo h($judgement_message); ?></p>
        </div>

        <div class="credits-summary">
            <p>総取得単位: <strong><?php echo $totalCredits; ?> 単位</strong></p>
            <?php if (!empty($categoryCredits)): ?>
                <ul>
                    <?php foreach ($categoryCredits as $category => $credits): ?>
                        <li><?php echo h($category); ?>：<?php echo h($credits); ?> 単位</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <a href="index.php" class="back-button">時間割作成に戻る</a>
    </div>

    <?php 
    $user_id_for_js = $loggedIn ? json_encode($user_id) : 'null';
    echo "<script> const currentUserIdFromPHP = {$user_id_for_js};</script>";
    ?>
    <script src="credits_status.js" defer></script>
</body>
</html>
