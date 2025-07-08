<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 必要単位数取得（display_order順でソート）
$sql = "
    SELECT category1, category2, category3, required_credits, display_order
    FROM requirements
    ORDER BY display_order ASC
";
$req_stmt = $db->query($sql);
$requirements = $req_stmt->fetchAll(PDO::FETCH_ASSOC);

// 重複なしの subject_id を取得し、分類ごとに集計＋科目名も取得
$sql2 = "
    SELECT s.name, s.category1, s.category2, s.category3, s.credit
    FROM (
        SELECT DISTINCT subject_id
        FROM timetables
        WHERE user_id = ?
    ) AS uniq
    JOIN subjects s ON uniq.subject_id = s.id
";
$earn_stmt = $db->prepare($sql2);
$earn_stmt->execute([$user_id]);
$subjects = $earn_stmt->fetchAll(PDO::FETCH_ASSOC);

// 集計処理
$earned_map = [];
$total_earned = 0;
$earned_category1 = [];
$earned_category2 = [];
$earned_category3 = [];
$earned_subject_names = [];

foreach ($subjects as $row) {
    $key = "{$row['category1']}|{$row['category2']}|{$row['category3']}";
    $credit = (int)$row['credit'];
    $total_earned += $credit;

    $earned_subject_names[] = $row['name'];

    if (!isset($earned_map[$key])) $earned_map[$key] = 0;
    $earned_map[$key] += $credit;

    if (!isset($earned_category1[$row['category1']])) $earned_category1[$row['category1']] = 0;
    $earned_category1[$row['category1']] += $credit;

    if (!isset($earned_category2[$row['category2']])) $earned_category2[$row['category2']] = 0;
    $earned_category2[$row['category2']] += $credit;

    if (!isset($earned_category3[$row['category3']])) $earned_category3[$row['category3']] = 0;
    $earned_category3[$row['category3']] += $credit;
}

// 必修科目一覧
$required_subjects = ['ゼミナール1', 'ゼミナール2', '課題研究', '日本語表現法', '課題探究セミナー'];

// category2（進級段階など）ごとにグループ化（ただし卒業はまとめる）
$grouped = [];
foreach ($requirements as $row) {
    if ($row['category1'] === '卒業') {
        $grouped['卒業要件まとめ'][] = $row;
    } else {
        $grouped[$row['category2']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>不足単位確認</title>
    <link rel="stylesheet" href="css/shortage.css">
    <style>
        h2 {
            margin-top: 40px;
            font-size: 1.4em;
            color: #2c3e50;
            border-left: 5px solid #2980b9;
            padding-left: 10px;
            cursor: pointer;
            user-select: none;
        }
        .collapsed + table {
            display: none;
        }
        h2::before {
            content: "▼ ";
            display: inline-block;
            transition: transform 0.3s ease;
        }
        .collapsed::before {
            content: "▶ ";
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>不足単位の確認</h1>

        <?php foreach ($grouped as $stage => $rows): ?>
            <h2 class="toggle-header collapsed">
                <?= htmlspecialchars($stage === '卒業要件まとめ' ? '卒業要件（教養科目・専門科目含む）' : $stage) ?>
            </h2>
            <table style="display:none">
                <thead>
                    <tr>
                        <th>大分類 (category1)</th>
                        <th>中分類 (category2)</th>
                        <th>小分類 (category3)</th>
                        <th>必要単位数</th>
                        <th>取得済</th>
                        <th>不足単位</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <?php
                            $category1 = $row['category1'];
                            $category2 = $row['category2'];
                            $category3 = $row['category3'];
                            $required = (int)$row['required_credits'];

                            if ($category3 === '総単位') {
                                $earned = $total_earned;
                            } elseif (in_array($category3, $required_subjects)) {
                                $earned = in_array($category3, $earned_subject_names) ? 1 : 0;
                            } elseif (isset($earned_category3[$category3])) {
                                $earned = $earned_category3[$category3];
                            } elseif (isset($earned_category2[$category3])) {
                                $earned = $earned_category2[$category3];
                            } elseif (isset($earned_category1[$category3])) {
                                $earned = $earned_category1[$category3];
                            } else {
                                $key = "{$category1}|{$category2}|{$category3}";
                                $earned = $earned_map[$key] ?? 0;
                            }

                            $shortage = max(0, $required - $earned);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($category1) ?></td>
                            <td><?= htmlspecialchars($category2) ?></td>
                            <td>
                                <?= htmlspecialchars($category3) ?>
                                <?= in_array($category3, $required_subjects) ? "<span style='color:blue'>(必修)</span>" : "" ?>
                            </td>
                            <td><?= $required ?></td>
                            <td><?= $earned ?></td>
                            <td><?= $shortage > 0 ? "<strong style='color:red'>{$shortage}</strong>" : "0" ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>

        <div class="buttons">
            <a href="menu.php" class="btn green">メニューに戻る</a>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="index.php" class="nav-button">メニュー</a>
        <a href="timetable_register.php" class="nav-button">時間割登録</a>
        <a href="timetable_confirm.php" class="nav-button">時間割確認</a>
        <a href="credits.php" class="nav-button">取得単位確認</a>
    </div>

    <script>
        document.querySelectorAll('.toggle-header').forEach(header => {
            header.addEventListener('click', () => {
                header.classList.toggle('collapsed');
                const table = header.nextElementSibling;
                if (table) {
                    if (table.style.display === 'none') {
                        table.style.display = '';
                    } else {
                        table.style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>
