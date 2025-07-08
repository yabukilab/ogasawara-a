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

                <!-- 時間割テーブル（ここは省略。元のコードを維持） -->

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
