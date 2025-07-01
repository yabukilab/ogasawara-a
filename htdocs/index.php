<?php
// session_start(); 는 파일 최상단에 있어야 합니다.
session_start();

// 데이터베이스 설정 파일 포함
require_once 'db_config.php';

// 로그인 상태 확인
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// --- classes 테이블에서 수업 목록 가져오기 ---
$classes = [];
try {
    $stmt = $db->prepare("SELECT id, grade, term, name, category1, category2, category3, credit FROM classes ORDER BY grade, term, name");
    $stmt->execute();
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("授業データ取得エラー: " . $e->getMessage());
    // 실제 서비스에서는 사용자에게 친화적인 메시지를 표시하거나 리다이렉트할 수 있습니다.
}

// --- user_timetables에서 사용자 시간표 데이터 가져오기 ---
$user_timetable_data = [];
if ($is_logged_in) {
    try {
        $stmt = $db->prepare("SELECT ut.day, ut.period, ut.grade AS timetable_grade, ut.class_id,
                                    c.name AS class_name, c.credit, c.term, c.teacher_name, c.room_number
                             FROM user_timetables ut
                             JOIN classes c ON ut.class_id = c.id
                             WHERE ut.user_id = :user_id
                             ORDER BY ut.day, ut.period");
        $stmt->bindParam(':user_id', $current_user_id);
        $stmt->execute();
        $user_timetable_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("ユーザー時間割データ取得エラー: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成システム</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>"> </head>
<body>
    <div class="user-info">
        <?php if ($is_logged_in): ?>
            <span>ようこそ、<?php echo htmlspecialchars($_SESSION['username']); ?>さん！</span>
            <a href="logout.php">ログアウト</a>
        <?php else: ?>
            <div class="auth-links">
                <a href="login.php">ログイン</a>
                <a href="register.php">新規登録</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="container">
        <h1>時間割作成</h1>

        <?php
        // 메시지 출력 (성공/에러 메시지)
        if (isset($_SESSION['message'])) {
            $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'success';
            echo "<div class='message {$message_type}'>" . htmlspecialchars($_SESSION['message']) . "</div>";
            unset($_SESSION['message']); // 메시지 출력 후 제거
            unset($_SESSION['message_type']);
        }
        ?>

        <div class="filter-form">
            <label for="gradeSelectFilter">学年で絞り込み:</label>
            <select id="gradeSelectFilter">
                <option value="all">全学年</option>
                <option value="1">1年生</option>
                <option value="2">2年生</option>
                <option value="3">3年生</option>
                <option value="4">4年生</option>
                </select>
            <button id="applyFilterBtn">フィルター適用</button>
        </div>


        <div class="main-container">
            <div class="class-list-section">
                <h2>授業一覧</h2>
                <div class="filter-form">
                    <label for="classSearchInput">授業名で検索:</label>
                    <input type="text" id="classSearchInput" placeholder="授業名を入力">
                    <button id="searchClassBtn">検索</button>
                </div>

                <div class="filter-form">
                    <label for="termSelect">学期:</label>
                    <select id="termSelect">
                        <option value="all">全学期</option>
                        <option value="1">前期</option>
                        <option value="2">後期</option>
                    </select>

                    <label for="creditSelect">単位:</label>
                    <select id="creditSelect">
                        <option value="all">全て</option>
                        <option value="1">1単位</option>
                        <option value="2">2単位</option>
                        <option value="3">3単位</option>
                        <option value="4">4単位</option>
                    </select>
                </div>

                <table id="classesTable">
                    <thead>
                        <tr>
                            <th>授業名</th>
                            <th>学年</th>
                            <th>学期</th>
                            <th>単位</th>
                            <th></th> </tr>
                    </thead>
                    <tbody>
                        <script>
                            const allClasses = <?php echo json_encode($classes); ?>;
                            const userTimetableData = <?php echo json_encode($user_timetable_data); ?>;
                            const currentUserId = <?php echo json_encode($current_user_id); ?>;
                            const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
                        </script>
                    </tbody>
                </table>
            </div>

            <div class="timetable-section">
                <h2>あなたの時間割</h2>

                <div id="selectedClassInfo" style="display: none;">
                    <p>選択中の授業: <span id="currentSelectedClassName"></span> (<span id="currentSelectedClassCredit"></span>単位)</p>
                    <button id="addSelectedClassBtn">選択した授業を時間割に追加</button>
                </div>

                <p id="totalCredits">合計単位数: 0</p>

                <div style="text-align: center; margin-bottom: 20px;">
                    <label for="daySelect">曜日:</label>
                    <select id="daySelect">
                        <option value="月">月曜日</option>
                        <option value="火">火曜日</option>
                        <option value="水">水曜日</option>
                        <option value="木">木曜日</option>
                        <option value="金">金曜日</option>
                        <option value="土">土曜日</option>
                    </select>

                    <label for="periodSelect" style="margin-left: 20px;">時限:</label>
                    <select id="periodSelect">
                        <option value="1">1限</option>
                        <option value="2">2限</option>
                        <option value="3">3限</option>
                        <option value="4">4限</option>
                        <option value="5">5限</option>
                        <option value="6">6限</option>
                        <option value="7">7限</option>
                        <option value="8">8限</option>
                        <option value="9">9限</option>
                        <option value="10">10限</option>
                    </select>
                </div>

                <table class="timetable-table" id="timetable">
                    <thead>
                        <tr>
                            <th>時限</th>
                            <th>月曜日</th>
                            <th>火曜日</th>
                            <th>水曜日</th>
                            <th>木曜日</th>
                            <th>金曜日</th>
                            <th>土曜日</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>

                <button id="confirmTimetableBtn">登録確定</button>
                <?php if (!$is_logged_in): ?>
                    <p style="text-align: center; font-size: 0.9em; color: #666; margin-top: 10px;">時間割を保存するには<a href="login.php">ログイン</a>してください。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="script.js?v=<?php echo time(); ?>"></script> </body>
</html>