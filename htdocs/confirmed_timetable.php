<?php
session_start(); // セッション開始
require_once 'db.php'; // データベース接続およびh()関数使用のため包含

$loggedIn = isset($_SESSION['user_id']);
$student_number = $_SESSION['student_number'] ?? 'ゲスト';
$department = $_SESSION['department'] ?? '';

// PHPでh()関数が定義されていなければ下記関数を追加します。
// 通常db.phpまたはcommon.phpのようなファイルに含まれています。
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// ディバッグ用セッション値出力 (ページ上部に表示)
echo "<p style='color: red; font-weight: bold;'>デバッグ: セッション user_id = " . ($_SESSION['user_id'] ?? 'NULL') . "</p>";

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確定済み時間割 (Confirmed Timetable)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body data-user-id="<?php echo $loggedIn ? h($_SESSION['user_id']) : 'null'; ?>">
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

        <h1>確定済み時間割</h1>

        <div class="timetable-selection" style="margin-bottom: 15px; text-align: center;">
            <h3>表示する時間割を選択:</h3>
            <label for="confirmedTimetableGradeSelect">学年:</label>
            <select id="confirmedTimetableGradeSelect">
                <option value="1">1年生</option>
                <option value="2">2年生</option>
                <option value="3">3年生</option>
                <option value="4">4年生</option>
            </select>
            <label for="confirmedTimetableTermSelect" style="margin-left: 10px;">学期:</label>
            <select id="confirmedTimetableTermSelect">
                <option value="前期">前期</option>
                <option value="後期">後期</option>
            </select>
        </div>

        <div id="confirmed-timetable-message" style="text-align: center; margin-top: 10px; color: red;"></div>

        <table id="confirmed-timetable-table" class="timetable-table">
            <thead>
                <tr>
                    <th>時間/曜日</th>
                    <th>月曜日</th>
                    <th>火曜日</th>
                    <th>水曜日</th>
                    <th>木曜日</th>
                    <th>金曜日</th>
                    <th>土曜日</th>
                </tr>
            </thead>
            <tbody id="confirmed-timetable-body">
                <?php
                $periods = range(1, 10); // 1교시부터 10교시까지
                $days_ja = ['月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日']; // 일본어 요일
                $days_en = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; // 영어 요일 (JavaScript에서 사용할 예정)

                foreach ($periods as $period) {
                    echo "<tr>";
                    echo "<td class='period-header-cell'>{$period}限<span class='period-time'>";
                    // 時間表示のための追加ロジック (希望の形式に調整)
                    switch ($period) {
                        case 1: echo "9:00-10:00"; break;
                        case 2: echo "10:00-11:00"; break;
                        case 3: echo "11:00-12:00"; break;
                        case 4: echo "12:00-13:00"; break;
                        case 5: echo "13:00-14:00"; break;
                        case 6: echo "14:00-15:00"; break;
                        case 7: echo "15:00-16:00"; break;
                        case 8: echo "16:00-17:00"; break;
                        case 9: echo "17:00-18:00"; break;
                        case 10: echo "18:00-19:00"; break;
                    }
                    echo "</span></td>";
                    foreach ($days_en as $index => $day_en) { // data-day 속성을 영어 요일로 설정
                        echo "<td class='time-slot' data-day='{$day_en}' data-period='{$period}'></td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="total-credits-display" style="text-align: center; margin-top: 20px;">
            <p>合計取得単位: <span id="total-credits">0</span> 単位</p>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" class="view-confirmed-button">時間割作成に戻る</a>
            <a href="credits_status.php" class="view-confirmed-button">単位取得状況を確認</a>
        </div>
    </div>

    <script src="confirmed_timetable.js" defer></script>
</body>
</html>