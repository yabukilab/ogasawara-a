<?php
// timetable_register.php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$grade = $_GET['grade'] ?? 1;
$term = $_GET['term'] ?? '前期';

// 科目の取得
$stmt = $db->prepare("SELECT * FROM subjects WHERE grade = ? AND term = ?");
$stmt->execute([$grade, $term]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare("SELECT t.day, t.period, s.name, s.id AS subject_id FROM timetables t JOIN subjects s ON t.subject_id = s.id WHERE t.user_id = ? AND t.grade = ? AND t.term = ?");
$stmt2->execute([$user_id, $grade, $term]);
$registeredTimetable = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>時間割登録</title>
    <link rel="stylesheet" href="css/timetable.css">
    <script>
        const subjects = <?= json_encode($subjects, JSON_UNESCAPED_UNICODE) ?>;
        const registeredTimetable = <?= json_encode($registeredTimetable, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="js/timetable.js" defer></script>
</head>
<body>
<div class="container">
    <h1>時間割登録</h1>
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

    <div class="timetable-section">
        <div class="subject-list">
            <h2>科目一覧</h2>
            <ul id="subjectList">
                <?php foreach ($subjects as $subject): ?>
                    <li data-id="<?= $subject['id'] ?>"><?= htmlspecialchars($subject['name']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="timetable">
            <h2>時間割表</h2>
            <div class="timetable-wrapper">
                <table id="timetable">
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
                                    <td data-day="<?= $j ?>" data-period="<?= $i ?>"></td>
                                <?php endfor; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <form method="POST" action="save_timetable.php" id="saveForm">
                <input type="hidden" name="data" id="timetableData">
                <input type="hidden" name="grade" value="<?= htmlspecialchars($grade) ?>">
                <input type="hidden" name="term" value="<?= htmlspecialchars($term) ?>">
                <button type="submit">保存する</button>
            </form>
        </div>
    </div>
</div>
<div class="bottom-nav">
    <a href="index.php" class="nav-button">メニュー</a>
    <a href="timetable_confirm.php" class="nav-button">時間割確認</a>
    <a href="credits.php" class="nav-button">取得単位確認</a>
    <a href="shortage.php" class="nav-button">不足単位確認</a>
</div>
</body>
</html>
