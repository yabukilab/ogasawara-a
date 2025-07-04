<?php
session_start();
require_once 'db.php'; // 데이터베이스 연결 및 h() 함수 사용을 위해 포함

// 현재 로그인된 사용자의 정보 설정
$loggedIn = isset($_SESSION['user_id']);
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // 게스트 (Guest)
$department = $_SESSION['department'] ?? '';

// PHP에서 h() 함수가 정의되어 있지 않다면 아래 함수를 추가합니다.
// 보통 db.php 또는 common.php 같은 파일에 포함되어 있습니다.
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

// 이전의 $user_id_for_js 변수 선언은 제거합니다.
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成 (Timetable Creation)</title>
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

        <h1>時間割作成</h1>

        <div class="main-container">
            <div class="class-list-section">
                <h2>授業リスト</h2>
                <form id="classFilterForm" class="filter-form">
                    <label for="gradeFilter">学年:</label>
                    <select id="gradeFilter" name="grade">
                        <option value="">全て</option>
                        <option value="1">1年</option>
                        <option value="2">2年</option>
                        <option value="3">3年</option>
                        <option value="4">4年</option>
                    </select>

                    <label for="termFilter">学期:</label>
                    <select id="termFilter" name="term">
                        <option value="">全て</option>
                        <option value="前期">前期</option>
                        <option value="後期">後期</option>
                    </select>

                    <button type="submit">フィルター</button>
                </form>
                <div id="lesson-list-container" class="class-list-container">
                    <p>授業を読み込み中...</p>
                </div>
            </div>

            <div class="timetable-section">
                <h2>私の時間割</h2>
                <div class="timetable-selection" style="margin-bottom: 15px; text-align: center;">
                    <h3>表示する時間割の学年を選択:</h3>
                    <select id="timetableGradeSelect">
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="3">3年生</option>
                        <option value="4">4年生</option>
                    </select>
                </div>

                <table id="timetable-table" class="timetable-table">
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
                    <tbody>
                        <?php
                        $periods = range(1, 6); // 1교시부터 6교시까지
                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']; // 영어 요일로 변경

                        foreach ($periods as $period) {
                            echo "<tr>";
                            echo "<td class='period-header-cell'>{$period}限<span class='period-time'>";
                            // 시간 표시를 위한 추가 로직 (원하는 형식으로 조정)
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
                            foreach ($days as $day) {
                                // data-day 속성을 영어 요일로 설정
                                echo "<td class='time-slot' data-day='{$day}' data-period='{$period}'></td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
                <div style="text-align: center; margin-top: 20px;">
                    <button id="saveTimetableBtn">時間割を保存</button>
                    <a href="confirmed_timetable.php" class="view-confirmed-button">確定済み時間割を見る</a>
                    <a href="credits_status.php" class="view-confirmed-button">単位取得状況を確認</a>
                </div>
            </div>
        </div>
    </div>

    <script src="main_script.js" defer></script>
</body>
</html>