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
    <title>単位取得状況 (Credit Status)</title>
    <link rel="stylesheet" href="style.css">             <link rel="stylesheet" href="credits_status.css">   </head>
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

        <h1>単位取得状況</h1>

        <div id="credits-status-message" class="message-container">
            <p>単位取得状況を読み込み中...</p>
        </div>

        <div class="credits-summary">
            <p>総取得単位: <span id="total-credits">0</span>単位</p>
        </div>

        <h2>カテゴリー別取得単位</h2>
        <ul id="category-credits-list">
            <li>データ読み込み中...</li>
        </ul>

        <a href="index.php" class="back-button">時間割作成に戻る</a>
    </div>

    <?php 
    $user_id_for_js = isset($_SESSION['user_id']) ? json_encode($_SESSION['user_id']) : 'null';
    echo "<script> const currentUserIdFromPHP = {$user_id_for_js};</script>";
    ?>
    <script src="credits_status.js" defer></script> 
</body>
</html>