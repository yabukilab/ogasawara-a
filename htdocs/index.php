<?php
session_start();
require_once 'db.php'; // データ베이스接続及びh()関数使用をために含む

// 現在ログインされたユーザーの情報設定
$loggedIn = isset($_SESSION['user_id']);
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // ゲスト (Guest)
$department = $_SESSION['department'] ?? '';

// JavaScriptに伝達するユーザーID設定
// currentUserIdFromPHP変数はmain_script.jsで使われます。
$user_id_for_js = $loggedIn ? json_encode($_SESSION['user_id']) : 'null';
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
<body data-user-id="<?php echo $user_id_for_js; ?>"> 
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

                    <label for="facultyFilter">区分 (Category 1):</label>
                    <select id="facultyFilter" name="faculty">
                        <option value="">全て</option>
                        <option value="専門科目">専門科目</option>
                        <option value="教養科目">教養科目</property>
                        </select>
                    
                    <button type="submit">フィルター</button>
                </form>
                <div id="lesson-list-container" class="class-list-container">
                    <p>授業を読み込み中...</p>
                </div>
            </div>

            <div class="timetable-section">
                <h2>私の時間割</h2>
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
                            <td class="period-header-cell">1限<span class="period-time">9:00-10:30</span></td>
                            <td class="time-slot" data-day="Monday" data-period="1"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="1"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="1"></td>
                            <td class="time-slot" data-day="Thursday" data-period="1"></td>
                            <td class="time-slot" data-day="Friday" data-period="1"></td>
                            <td class="time-slot" data-day="Saturday" data-period="1"></td>
                        </tr>
                        <tr>
                            <td class="period-header-cell">2限<span class="period-time">10:40-12:10</span></td>
                            <td class="time-slot" data-day="Monday" data-period="2"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="2"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="2"></td>
                            <td class="time-slot" data-day="Thursday" data-period="2"></td>
                            <td class="time-slot" data-day="Friday" data-period="2"></td>
                            <td class="time-slot" data-day="Saturday" data-period="2"></td>
                        </tr>
                        <tr>
                            <td class="period-header-cell">3限<span class="period-time">13:00-14:30</span></td>
                            <td class="time-slot" data-day="Monday" data-period="3"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="3"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="3"></td>
                            <td class="time-slot" data-day="Thursday" data-period="3"></td>
                            <td class="time-slot" data-day="Friday" data-period="3"></td>
                            <td class="time-slot" data-day="Saturday" data-period="3"></td>
                        </tr>
                        <tr>
                            <td class="period-header-cell">4限<span class="period-time">14:40-16:10</span></td>
                            <td class="time-slot" data-day="Monday" data-period="4"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="4"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="4"></td>
                            <td class="time-slot" data-day="Thursday" data-period="4"></td>
                            <td class="time-slot" data-day="Friday" data-period="4"></td>
                            <td class="time-slot" data-day="Saturday" data-period="4"></td>
                        </tr>
                        <tr>
                            <td class="period-header-cell">5限<span class="period-time">16:20-17:50</span></td>
                            <td class="time-slot" data-day="Monday" data-period="5"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="5"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="5"></td>
                            <td class="time-slot" data-day="Thursday" data-period="5"></td>
                            <td class="time-slot" data-day="Friday" data-period="5"></td>
                            <td class="time-slot" data-day="Saturday" data-period="5"></td>
                        </tr>
                        <tr>
                            <td class="period-header-cell">6限<span class="period-time">18:00-19:30</span></td>
                            <td class="time-slot" data-day="Monday" data-period="6"></td>
                            <td class="time-slot" data-day="Tuesday" data-period="6"></td>
                            <td class="time-slot" data-day="Wednesday" data-period="6"></td>
                            <td class="time-slot" data-day="Thursday" data-period="6"></td>
                            <td class="time-slot" data-day="Friday" data-period="6"></td>
                            <td class="time-slot" data-day="Saturday" data-period="6"></td>
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

    <script src="main_script.js"></script> 
</body>
</html>