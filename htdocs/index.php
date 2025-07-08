<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$student_number = htmlspecialchars($_SESSION['student_number']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>メニュー</title>
    <link rel="stylesheet" href="css/menu.css">
</head>
<body>
    <div class="menu-container">
        <h1>取得単位確認システム</h1>
        <p>ようこそ、<?= $student_number ?> さん</p>

        <div class="menu-buttons">
            <a href="timetable_register.php" class="menu-button">時間割を登録する</a>
            <a href="timetable_confirm.php" class="menu-button">時間割を確認する</a>
            <a href="credits.php" class="menu-button">取得単位を確認する</a>
            <a href="shortage.php" class="menu-button">不足単位を確認する</a>
        </div>

        <form method="post" action="logout.php">
            <button type="submit" class="logout-button">ログアウト</button>
        </form>
    </div>
</body>
</html>
