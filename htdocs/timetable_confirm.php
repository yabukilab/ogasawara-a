<?php
// timetable_confirm.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$grade = $_GET['grade'] ?? 1;
$term = $_GET['term'] ?? '前期';

$sql = "SELECT t.day, t.period, s.name 
        FROM timetables t
        JOIN subjects s ON t.subject_id = s.id
        WHERE t.user_id = ? AND t.grade = ? AND t.term = ?";
$stmt = $db->prepare($sql);
$stmt->execute([$user_id, $grade, $term]);
$timetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$timetable = [];
foreach ($timetableData as $entry) {
    $day = $entry['day'];
    $period = $entry['period'];
    $timetable[$day][$period] = $entry['name'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>時間割確認</title>
    <link rel="stylesheet" href="css/timetable_confirm.css">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

</head>
<body>
<div class="container">
    <h1>時間割確認</h1>

    <form method="GET" id="termForm">
        <label>学年:
            <select name="grade" onchange="document.getElementById('termForm').submit();">
                <?php for ($i = 1; $i <= 4; $i++): ?>
                    <option value="<?= $i ?>" <?= $grade == $i ? 'selected' : '' ?>><?= $i ?>年</option>
                <?php endfor; ?>
            </select>
        </label>
        <label>学期:
            <select name="term" onchange="document.getElementById('termForm').submit();">
                <option value="前期" <?= $term === '前期' ? 'selected' : '' ?>>前期</option>
                <option value="後期" <?= $term === '後期' ? 'selected' : '' ?>>後期</option>
            </select>
        </label>
    </form>

    <table>
        <thead>
            <tr>
                <th>時限＼曜日</th>
                <?php foreach (["月","火","水","木","金","土"] as $day): ?>
                    <th><?= $day ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <tr>
                    <th><?= $i ?>限</th>
                    <?php for ($j = 0; $j < 6; $j++): ?>
                        <td><?= isset($timetable[$j][$i]) ? htmlspecialchars($timetable[$j][$i]) : '' ?></td>
                    <?php endfor; ?>
                </tr>
            <?php endfor; ?>
        </tbody>
    </table>

    <div class="buttons">
        <a href="timetable_register.php" class="btn">時間割を登録・修正する</a>
        <a href="credits.php" class="btn blue">取得単位を確認する</a>
    </div>
</div>
<div class="bottom-nav">
    <a href="menu.php" class="nav-button">メニュー</a>
    <a href="timetable_register.php" class="nav-button">時間割登録</a>
    <a href="credits.php" class="nav-button">取得単位確認</a>
    <a href="shortage.php" class="nav-button">不足単位確認</a>
</div>
</body>
</html>