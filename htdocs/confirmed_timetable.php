<?php
session_start(); // 세션 시작

// db.php 파일을 포함하여 데이터베이스 연결 및 h() 함수 사용
// h() 함수는 XSS 방지를 위해 HTML 엔티티로 변환하는 함수입니다.
// db.php에 다음과 같은 함수가 정의되어 있다고 가정합니다:
// function h($string) { return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
require_once 'db.php'; 

// 현재 로그인된 사용자의 정보 설정
$loggedIn = isset($_SESSION['user_id']);
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // 게스트 (Guest)
$department = $_SESSION['department'] ?? ''; // 사용자의 소속 학부/학과 정보
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確定済み時間割 (Confirmed Timetable)</title>
    <link rel="stylesheet" href="style.css">               <link rel="stylesheet" href="confirmed_timetable.css"> </head>
<body>
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

        <div id="confirmed-timetable-message" class="message-container">
            <p>時間割を読み込み中...</p>
        </div>

        <table id="confirmed-timetable-table" class="confirmed-timetable-table">
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

        <a href="index.php" class="back-button">時間割作成に戻る</a>
    </div>

    <?php 
    $user_id_for_js = isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null';
    echo "<script>const currentUserIdFromPHP = {$user_id_for_js};</script>";
    ?>
    <script src="confirmed_timetable.js" defer></script> 
</body>
</html>