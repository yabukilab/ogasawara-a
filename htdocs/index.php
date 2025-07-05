<?php
session_start(); // 세션 시작
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
                <div id="total-credit-display" style="margin-top: 20px; font-size: 1.2em; font-weight: bold;">
                   登録合計単位数: <span id="current-total-credit">0</span>単位
                </div>
                <div class="timetable-selection" style="margin-bottom: 15px; text-align: center;">
                    <h3>表示する時間割を選択:</h3>
                    <label for="timetableGradeSelect">学年:</label>
                    <select id="timetableGradeSelect">
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="3">3年生</option>
                        <option value="4">4年生</option>
                    </select>
                    <label for="timetableTermSelect" style="margin-left: 10px;">学期:</label>
                    <select id="timetableTermSelect">
                        <option value="前期">前期</option>
                        <option value="後期">後期</option>
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
                            <tr>
                                <td class='period-header-cell'>
                                    1限<span class='period-time'>9:00-10:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='1'></td>
                                <td class='time-slot' data-day='火' data-period='1'></td>
                                <td class='time-slot' data-day='水' data-period='1'></td>
                                <td class='time-slot' data-day='木' data-period='1'></td>
                                <td class='time-slot' data-day='金' data-period='1'></td>
                                <td class='time-slot' data-day='土' data-period='1'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    2限<span class='period-time'>10:00-11:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='2'></td>
                                <td class='time-slot' data-day='火' data-period='2'></td>
                                <td class='time-slot' data-day='水' data-period='2'></td>
                                <td class='time-slot' data-day='木' data-period='2'></td>
                                <td class='time-slot' data-day='金' data-period='2'></td>
                                <td class='time-slot' data-day='土' data-period='2'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    3限<span class='period-time'>11:00-12:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='3'></td>
                                <td class='time-slot' data-day='火' data-period='3'></td>
                                <td class='time-slot' data-day='水' data-period='3'></td>
                                <td class='time-slot' data-day='木' data-period='3'></td>
                                <td class='time-slot' data-day='金' data-period='3'></td>
                                <td class='time-slot' data-day='土' data-period='3'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    4限<span class='period-time'>12:00-13:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='4'></td>
                                <td class='time-slot' data-day='火' data-period='4'></td>
                                <td class='time-slot' data-day='水' data-period='4'></td>
                                <td class='time-slot' data-day='木' data-period='4'></td>
                                <td class='time-slot' data-day='金' data-period='4'></td>
                                <td class='time-slot' data-day='土' data-period='4'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    5限<span class='period-time'>13:00-14:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='5'></td>
                                <td class='time-slot' data-day='火' data-period='5'></td>
                                <td class='time-slot' data-day='水' data-period='5'></td>
                                <td class='time-slot' data-day='木' data-period='5'></td>
                                <td class='time-slot' data-day='金' data-period='5'></td>
                                <td class='time-slot' data-day='土' data-period='5'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    6限<span class='period-time'>14:00-15:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='6'></td>
                                <td class='time-slot' data-day='火' data-period='6'></td>
                                <td class='time-slot' data-day='水' data-period='6'></td>
                                <td class='time-slot' data-day='木' data-period='6'></td>
                                <td class='time-slot' data-day='金' data-period='6'></td>
                                <td class='time-slot' data-day='土' data-period='6'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    7限<span class='period-time'>15:00-16:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='7'></td>
                                <td class='time-slot' data-day='火' data-period='7'></td>
                                <td class='time-slot' data-day='水' data-period='7'></td>
                                <td class='time-slot' data-day='木' data-period='7'></td>
                                <td class='time-slot' data-day='金' data-period='7'></td>
                                <td class='time-slot' data-day='土' data-period='7'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    8限<span class='period-time'>16:00-17:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='8'></td>
                                <td class='time-slot' data-day='火' data-period='8'></td>
                                <td class='time-slot' data-day='水' data-period='8'></td>
                                <td class='time-slot' data-day='木' data-period='8'></td>
                                <td class='time-slot' data-day='金' data-period='8'></td>
                                <td class='time-slot' data-day='土' data-period='8'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    9限<span class='period-time'>17:00-18:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='9'></td>
                                <td class='time-slot' data-day='火' data-period='9'></td>
                                <td class='time-slot' data-day='水' data-period='9'></td>
                                <td class='time-slot' data-day='木' data-period='9'></td>
                                <td class='time-slot' data-day='金' data-period='9'></td>
                                <td class='time-slot' data-day='土' data-period='9'></td>
                            </tr>
                            <tr>
                                <td class='period-header-cell'>
                                    10限<span class='period-time'>18:00-19:00</span>
                                </td>
                                <td class='time-slot' data-day='月' data-period='10'></td>
                                <td class='time-slot' data-day='火' data-period='10'></td>
                                <td class='time-slot' data-day='水' data-period='10'></td>
                                <td class='time-slot' data-day='木' data-period='10'></td>
                                <td class='time-slot' data-day='金' data-period='10'></td>
                                <td class='time-slot' data-day='土' data-period='10'></td>
                            </tr>
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