<?php
session_start();
// db_config.php ファイルを読み込みます。これにより、$db オブジェクトと h() 関数、getTermName() 関数が利用可能になります。
require_once 'db_config.php';

// ログイン確認
if (!isset($_SESSION['student_number'])) {
    // ログインされていない場合、ログインページにリダイレクト
    header('Location: login.php');
    exit;
}

$current_student_number = $_SESSION['student_number'];
$selectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2; // デフォルトは2年生

// ユーザーの学科情報をロード (任意)
$current_user_department = '未設定';
try {
    // ここで $pdo ではなく $db を使用します。
    $stmt = $db->prepare("SELECT department FROM users WHERE student_number = :student_number");
    $stmt->execute([':student_number' => $current_student_number]);
    $user_info = $stmt->fetch();
    if ($user_info) {
        $current_user_department = htmlspecialchars($user_info['department']);
    }
} catch (PDOException $e) {
    error_log("Failed to load user department in confirmed_timetable: " . $e->getMessage());
    // ユーザーへの表示はしないが、エラーログには記録
}

// 確定済み時間割データをロード
$confirmedTimetableData = [];
$fetchError = ''; // データ取得エラーメッセージ初期化

try {
    // ここでも $pdo ではなく $db を使用します。
    $stmt = $db->prepare("SELECT ut.day, ut.period, ut.class_id,
                                 c.name as className, c.credit as classCredit, c.term as classTerm, c.grade as classGrade
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.student_number = :student_number AND ut.grade = :grade
                           ORDER BY ut.day, ut.period"); // 요일, 시한 순으로 정렬
    $stmt->execute([
        ':student_number' => $current_student_number,
        ':grade' => $selectedGrade
    ]);
    $confirmedTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to load confirmed timetable: " . $e->getMessage());
    $confirmedTimetableData = []; // エラー時はデータを空にする
    $fetchError = "確定済み時間割の読み込みに失敗しました。"; // ユーザーへのエラーメッセージ
}

// 時間時限定義
$times = [
    1 => '9:00-10:00', 2 => '10:00-11:00', 3 => '11:00-12:00',
    4 => '13:00-14:00', 5 => '14:00-15:00', 6 => '15:00-16:00',
    7 => '16:00-17:00', 8 => '17:00-18:00', 9 => '18:00-19:00', 10 => '19:00-20:00'
];
$days_of_week = ['月', '火', '水', '木', '金', '土']; // 曜日定義

// 総単位数計算
$totalCredits = 0;
$calculatedClassIds = []; // 重複単位計算防止
foreach ($confirmedTimetableData as $entry) {
    if (!in_array($entry['class_id'], $calculatedClassIds)) {
        $totalCredits += (int)$entry['classCredit'];
        $calculatedClassIds[] = $entry['class_id'];
    }
}

// 時間割データをマップ形式に変換してアクセスしやすくする
$timetableMap = [];
foreach ($days_of_week as $day_key => $day_name) { // $day_key を使用して 0-5 인덱스 생성
    $timetableMap[$day_name] = []; // 각 요일별로 빈 배열 초기화
    for ($i = 1; $i <= 10; $i++) { // 각 시한별로 초기화
        $timetableMap[$day_name][$i] = null; // 기본값은 null
    }
}

foreach ($confirmedTimetableData as $entry) {
    $timetableMap[$days_of_week[$entry['day']]][(int)$entry['period']] = $entry;
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確定済み時間割 (Confirmed Timetable)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* スタイルは変更なし */
        .navigation-buttons {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .navigation-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745; /* 緑系 */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            margin-right: 10px;
        }
        .navigation-buttons a:hover {
            background-color: #218838;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form label, .filter-form select {
            margin-right: 10px;
        }
        .timetable-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .timetable-table th, .timetable-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 0.9em;
        }
        .timetable-table th {
            background-color: #f2f2f2;
        }
        .time-slot {
            height: 80px; /* セルの高さを固定 */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .filled-primary {
            background-color: #e0f7fa; /* 水色系の背景 */
            font-weight: bold;
        }
        .term-display-in-cell {
            font-size: 0.7em;
            color: #007bff; /* 青色 */
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo $current_user_department; ?>)
        <a href="logout.php">ログアウト</a>
    </div>

    <h1>確定済み時間割</h1>

    <div class="navigation-buttons">
        <a href="index.php?grade_filter=<?= htmlspecialchars($selectedGrade) ?>">時間割を編集</a>
    </div>

    <div class="filter-form">
        <form action="confirmed_timetable.php" method="get">
            <label for="grade_filter">表示学年:</label>
            <select name="grade_filter" id="grade_filter" onchange="this.form.submit()">
                <?php
                for ($g = 1; $g <= 4; $g++) {
                    echo "<option value='{$g}'" . ($selectedGrade === $g ? ' selected' : '') . ">{$g}年生</option>";
                }
                ?>
            </select>
        </form>
    </div>

    <?php if (!empty($fetchError)): ?>
        <p class="message error"><?php echo h($fetchError); ?></p>
    <?php elseif (empty($confirmedTimetableData)): ?>
        <p>この学年の確定済み時間割はありません。</p>
    <?php else: ?>
        <table class="timetable-table">
            <thead>
                <tr>
                    <th>時限</th>
                    <?php foreach ($days_of_week as $day_name): ?>
                        <th><?= htmlspecialchars($day_name) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($times as $i => $time_range) { // $iは1から10までの時限
                    echo "<tr>";
                    echo "<td>" . $i . "限<br><span style='font-size:0.8em; color:#666;'>" . explode('-', $time_range)[0] . "</span></td>"; // 開始時間のみ表示

                    foreach ($days_of_week as $day_key => $day_name) { // $day_key を使用して timetableMap から正しい曜日データにアクセス
                        $cellContent = '';
                        $cellClasses = 'time-slot';
                        $termDisplayInCell = '';

                        // timetableMap から該当する曜日・時限のデータを取得
                        // $day_name を直接キーとして 사용
                        $classEntry = $timetableMap[$day_name][$i] ?? null;

                        if ($classEntry) {
                            $cellContent = htmlspecialchars($classEntry['className']) . "<br>(" . htmlspecialchars($classEntry['classCredit']) . "単位)";
                            $cellClasses .= ' filled-primary'; // 確定済み時間割ではすべて埋まっているセル
                            // getTermName 関数を呼び出して学期名を表示
                            $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($classEntry['classTerm']) . "</div>";
                        }
                        echo "<td class='{$cellClasses}'>{$cellContent}{$termDisplayInCell}</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="totalCredits">合計単位数: <?= htmlspecialchars($totalCredits) ?></div>
    <?php endif; ?>
</body>
</html>