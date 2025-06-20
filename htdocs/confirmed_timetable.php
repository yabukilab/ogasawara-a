<?php
session_start(); // セッション開始

// ログイン確認
$is_logged_in = isset($_SESSION['student_number']);
$current_student_number = $is_logged_in ? htmlspecialchars($_SESSION['student_number']) : 'ゲスト';

// 사용자의 학번이 없으면 로그인 페이지로 리다이렉트
if (!$is_logged_in) {
    header('Location: login.php');
    exit;
}

// URL パラメータから学年と学期を取得
$selectedGrade = isset($_GET['grade']) ? (int)$_GET['grade'] : 2; // デフォルトは2年生
$selectedTerm = isset($_GET['term']) ? (int)$_GET['term'] : 1;   // デフォルトは1 (前期)

// 学年と学期の有効性検査
if ($selectedGrade < 1 || $selectedGrade > 4) {
    $selectedGrade = 2;
}
if ($selectedTerm < 1 || $selectedTerm > 2) {
    $selectedTerm = 1; // 無効な値はデフォルトで前期に設定
}

// 時間割データファイルパス
$timetableFilePath = __DIR__ . '/confirmed_timetable_data_student' . $_SESSION['student_number'] . '_grade' . $selectedGrade . '.json';
$timetableData = [];
$loadTimetableSuccess = false;

if (file_exists($timetableFilePath)) {
    $jsonContent = file_get_contents($timetableFilePath);
    if ($jsonContent !== false) {
        $decodedData = json_decode($jsonContent, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
            $timetableData = $decodedData;
            $loadTimetableSuccess = true;
        } else {
            error_log("Failed to decode JSON from " . $timetableFilePath . ": " . json_last_error_msg());
        }
    } else {
        error_log("Failed to read file: " . $timetableFilePath);
    }
} else {
    $message = "選択された学年 ({$selectedGrade}年生) の時間割はまだ登録されていません。";
    $messageType = "info";
}

// コメント関連のコードは完全に削除済み

function getTermName($term_num) {
    switch ($term_num) {
        case 1: return '前期'; // 前期
        case 2: return '後期'; // 後期
        default: return '不明'; // 不明 (このケースは新しいロジックでは発生しないはず)
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割表</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 900px; margin: 30px auto; padding: 25px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .user-info a {
            color: #007bff;
            text-decoration: none;
            margin-left: 10px;
        }
        .user-info a:hover {
            text-decoration: underline;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; vertical-align: middle; }
        th { background-color: #f2f2f2; }
        /* 時限グループ化スタイル */
        .time-group-cell {
            vertical-align: top;
            padding: 8px;
            text-align: center;
            font-weight: bold;
            background-color: #e9e9e9;
            width: 10%;
        }
        .time-group-cell .time-range {
            font-size: 0.8em;
            color: #666;
            display: block;
        }
        /* 時間割スロット基本スタイル */
        .time-slot {
            min-height: 70px;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
            font-size: 0.9em;
            background-color: #ffffff;
            word-break: break-all; /* 長い授業名に対応 */
        }
        /* 授業が登録されているセル */
        .time-slot.class-filled {
            background-color: #e6f7ff; /* ライトブルー */
        }
        /* 連続授業の最初のセル */
        .time-slot.class-start {
            border-bottom: none;
        }
        /* 連続授業の2つ目のセル */
        .time-slot.class-continue {
            border-top: none;
            /* 連続授業の2つ目のセルには内容を入れない */
        }

        /* コメントがあるセル関連のスタイルは削除済み */

        .term-display-in-cell {
            font-size: 0.7em;
            color: #666;
            margin-top: 2px;
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .message.info {
            background-color: #e2f2f9;
            color: #2b708d;
            border-color: #b3e0f2;
        }
        .links {
            text-align: center;
            margin-top: 30px;
        }
        .links a {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .links a:hover {
            background-color: #0056b3;
        }
        .total-credits {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 20px;
            padding: 10px;
            background-color: #e0ffe0;
            border: 1px solid #a0d0a0;
            border-radius: 5px;
            text-align: center;
        }
        .filter-container {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        .filter-container select {
            padding: 8px 12px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 1em;
            min-width: 120px;
        }
        .filter-container label {
            font-weight: bold;
            margin-right: 5px;
            color: #555;
            line-height: 38px;
        }
        /* 削除ボタン */
        .remove-button { 
            background-color: #f44336; 
            color: white; 
            padding: 3px 6px; 
            border: none; 
            border-radius: 3px; 
            cursor: pointer; 
            font-size: 0.7em; 
            position: absolute; 
            top: 2px; 
            right: 2px;
            z-index: 10;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            ログイン中のユーザー: <?php echo $current_student_number; ?>
            <a href="logout.php">ログアウト</a>
        </div>

        <h1>時間割表</h1>

        <div class="filter-container">
            <div>
                <label for="grade_selector">表示する学年:</label>
                <select id="grade_selector" onchange="applyFilters()">
                    <?php for ($g = 1; $g <= 4; $g++): ?>
                        <option value="<?php echo $g; ?>" <?php echo ($selectedGrade === $g) ? 'selected' : ''; ?>>
                            <?php echo $g; ?>年生
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div>
                <label for="term_selector">表示する学期:</label>
                <select id="term_selector" onchange="applyFilters()">
                    <option value="1" <?php echo ($selectedTerm === 1) ? 'selected' : ''; ?>> (前期)</option>
                    <option value="2" <?php echo ($selectedTerm === 2) ? 'selected' : ''; ?>> (後期)</option>
                </select>
            </div>
        </div>

        <h2><?php echo $selectedGrade; ?>年生 - <?php echo getTermName($selectedTerm); ?> の確定時間割</h2>

        <?php
        if (isset($message)) {
            echo "<div class='message {$messageType}'>{$message}</div>";
        } elseif (!$loadTimetableSuccess) {
            echo "<div class='message error'>時間割データを読み込めませんでした。ファイルに問題があるか、データが不正です。</div>";
        }
        ?>

        <table>
            <thead>
                <tr>
                    <th>時限</th>
                    <th>月</th>
                    <th>火</th>
                    <th>水</th>
                    <th>木</th>
                    <th>金</th>
                    <th>土</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $times_grouped = [
                    '1,2限' => ['periods' => [1, 2], 'time_range' => '9:00-11:00'],
                    '3,4限' => ['periods' => [3, 4], 'time_range' => '11:00-13:00'],
                    '5,6限' => ['periods' => [5, 6], 'time_range' => '14:00-16:00'],
                    '7,8限' => ['periods' => [7, 8], 'time_range' => '16:00-18:00'],
                    '9,10限' => ['periods' => [9, 10], 'time_range' => '18:00-20:00']
                ];
                $days_of_week = ['月', '火', '水', '木', '金', '土'];
                $displayTimetable = [];

                foreach ($timetableData as $item) {
                    if (!isset($displayTimetable[$item['day']])) {
                        $displayTimetable[$item['day']] = [];
                    }
                    $displayTimetable[$item['day']][$item['period']] = $item;
                }

                $totalCredits = 0;
                $addedClassIds = [];

                foreach ($times_grouped as $label => $group_info) {
                    $start_period = $group_info['periods'][0];
                    $end_period = $group_info['periods'][1];
                    $time_range = $group_info['time_range'];

                    echo "<tr>";
                    echo "<td class='time-group-cell' rowspan='2'>";
                    echo $label . "<span class='time-range'>" . $time_range . "</span>";
                    echo "</td>";

                    // 最初の時限 (例: 1限, 3限) の行
                    foreach ($days_of_week as $day_name) {
                        $cellContent = '';
                        $cellClasses = 'time-slot';
                        $classInfo = $displayTimetable[$day_name][$start_period] ?? null;
                        
                        if ($classInfo && (int)$classInfo['classTerm'] === $selectedTerm) {
                            $is_primary_linked = false;
                            // linkedPeriod が存在し、isPrimary が 'true' の場合にのみ連続授業として処理
                            if (isset($classInfo['isPrimary']) && $classInfo['isPrimary'] === 'true' && isset($classInfo['linkedPeriod']) && (int)$classInfo['linkedPeriod'] === $end_period) {
                                $is_primary_linked = true;
                            }
                            
                            $cellContent = htmlspecialchars($classInfo['className']) . "<br>(" . htmlspecialchars($classInfo['classCredit']) . "単位)";
                            $cellContent .= "<div class='term-display-in-cell'>" . getTermName($classInfo['classTerm']) . "</div>";
                            $cellClasses .= ' class-filled';
                            
                            if ($is_primary_linked) {
                                $cellClasses .= ' class-start';
                            }
                            
                            // 削除ボタン
                            // index.php에서 linkedPeriod가 더 이상 사용되지 않으므로, 이 매개변수는 null을 전달하는 것으로 충분함
                            $cellContent .= "<button class='remove-button' " .
                                            "onclick='removeClassFromTimetable(\"" . $day_name . "\", " . $start_period . ", null)'>X</button>";

                            if (!in_array($classInfo['classId'], $addedClassIds)) {
                                $totalCredits += $classInfo['classCredit'];
                                $addedClassIds[] = $classInfo['classId'];
                            }
                        }
                        echo "<td class='{$cellClasses}' data-day='{$day_name}' data-time='{$start_period}'>{$cellContent}</td>";
                    }
                    echo "</tr>";

                    echo "<tr>";
                    // 2つ目の時限 (例: 2限, 4限) の行
                    foreach ($days_of_week as $day_name) {
                        $cellContent = '';
                        $cellClasses = 'time-slot';
                        $classInfoAtCurrentPeriod = $displayTimetable[$day_name][$end_period] ?? null;
                        $classInfoAtPreviousPeriod = $displayTimetable[$day_name][$start_period] ?? null;

                        // 現在の時限に直接授業が登録されており、フィルタ条件を満たす場合 (単独授業または連続授業の始まりではない場合)
                        if ($classInfoAtCurrentPeriod && (int)$classInfoAtCurrentPeriod['classTerm'] === $selectedTerm && (!isset($classInfoAtPreviousPeriod['isPrimary']) || (int)$classInfoAtPreviousPeriod['linkedPeriod'] !== $end_period)) {
                            $cellContent = htmlspecialchars($classInfoAtCurrentPeriod['className']) . "<br>(" . htmlspecialchars($classInfoAtCurrentPeriod['classCredit']) . "単位)";
                            $cellContent .= "<div class='term-display-in-cell'>" . getTermName($classInfoAtCurrentPeriod['classTerm']) . "</div>";
                            $cellClasses .= ' class-filled';

                            // 削除ボタン
                            $cellContent .= "<button class='remove-button' " .
                                            "onclick='removeClassFromTimetable(\"" . $day_name . "\", " . $end_period . ", null)'>X</button>";
                        }
                        // 前の時限の授業が連続授業の開始点で、このセルがその続きである場合
                        // この場合、セルには視覚的なテキスト内容を含まないように変更
                        elseif ($classInfoAtPreviousPeriod && isset($classInfoAtPreviousPeriod['isPrimary']) && $classInfoAtPreviousPeriod['isPrimary'] === 'true' && isset($classInfoAtPreviousPeriod['linkedPeriod']) && (int)$classInfoAtPreviousPeriod['linkedPeriod'] === $end_period && (int)$classInfoAtPreviousPeriod['classTerm'] === $selectedTerm) {
                            $cellClasses .= ' class-filled class-continue';
                            // 連続授業の2つ目のコマには内容を入れず、空のままにする
                            $cellContent = ''; 
                        }
                        echo "<td class='{$cellClasses}' data-day='{$day_name}' data-time='{$end_period}'>{$cellContent}</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="total-credits">合計単位数: <?php echo $totalCredits; ?></div>

        <div class="links">
            <a href="index.php">授業登録画面に戻る</a>
        </div>
    </div>

    <script>
        function applyFilters() {
            const gradeSelector = document.getElementById('grade_selector');
            const termSelector = document.getElementById('term_selector');
            const selectedGrade = gradeSelector.value;
            const selectedTerm = termSelector.value;

            window.location.href = `confirmed_timetable.php?grade=${selectedGrade}&term=${selectedTerm}`;
        }

        // 授業削除（表示のみのページのため、実際には登録画面で実施）
        async function removeClassFromTimetable(day, period, linkedPeriod = null) {
            if (!confirm('この授業を時間割から削除しますか？')) {
                return;
            }

            alert('このページでは授業を直接削除できません。「授業登録画面に戻る」をクリックして、該当画面で授業を削除してください。');
            
            // 削除を試行後、現在のフィルターを維持してページを再読み込みし、変更がないことを示します
            applyFilters(); 
        }
    </script>
</body>
</html>