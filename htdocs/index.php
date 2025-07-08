<?php
session_start();
require_once 'db.php'; // $db 객체 사용

if (!isset($_SESSION['user_id'])) {
    // 로그인하지 않은 경우 로그인 페이지로 리다이렉트
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // 게스트 (Guest)
$department = $_SESSION['department'] ?? '';

// 사용자가 저장한 시간표의 모든 고유 학년과 학기를 가져옵니다. (index.php에서도 필요)
$grades = [];
$terms = [];
try {
    $stmt_grades = $db->prepare("SELECT DISTINCT timetable_grade FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_grade ASC");
    $stmt_grades->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_grades->execute();
    $grades = $stmt_grades->fetchAll(PDO::FETCH_COLUMN);

    $stmt_terms = $db->prepare("SELECT DISTINCT timetable_term FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_term ASC");
    $stmt_terms->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_terms->execute();
    $terms = $stmt_terms->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Failed to fetch distinct grades/terms for user {$user_id} in index.php: " . $e->getMessage());
    // 사용자에게는 오류 메시지 표시 안 함, 빈 배열 유지
}

// 기본 학년/학기 설정 (가장 최근에 저장된 것을 기본으로 하거나, 1학년 1학기 등)
// 여기서는 가장 최근에 저장된 학년/학기를 기본값으로 설정하거나, 없으면 1학년 전기로 설정합니다.
$initial_grade = !empty($grades) ? max($grades) : '1'; // 가장 높은 학년 (최신 학년)
$initial_term = '前期'; // 기본 학기 (선호하는 학기로 설정)
if (!empty($terms) && in_array('後期', $terms)) { // 후기가 저장된 학기에 있으면 후기를 기본으로
    $initial_term = '後期';
}
// 하지만, 만약 특정 학년/학기로 저장된 것이 없다면, $initial_grade와 $initial_term을 사용할 수 있습니다.
// 여기서는 가장 최근에 저장된 학년/학기를 기본으로 합니다.
if (empty($grades) || empty($terms)) {
    // 저장된 시간표가 없으면 1학년 前期로 기본 설정
    $initial_grade = '1';
    $initial_term = '前期';
} else {
    // 가장 최근에 저장된 시간표의 학년/학기를 불러옵니다.
    // user_timetables 테이블에 타임스탬프 컬럼이 있다면 더 정확하게 최근 값을 가져올 수 있지만,
    // 여기서는 가장 높은 학년과 일반적인 학기 순서(前期 -> 後期)를 가정합니다.
    try {
        $stmt_latest = $db->prepare("SELECT timetable_grade, timetable_term FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_grade DESC, FIELD(timetable_term, '後期', '前期') DESC LIMIT 1");
        $stmt_latest->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_latest->execute();
        $latest_saved = $stmt_latest->fetch(PDO::FETCH_ASSOC);

        if ($latest_saved) {
            $initial_grade = $latest_saved['timetable_grade'];
            $initial_term = $latest_saved['timetable_term'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch latest saved timetable grade/term for user {$user_id}: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成 (Timetable Creation)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body data-user-id="<?php echo h($user_id); ?>">
    <div class="container">
        <div class="user-info">
            <p>ようこそ、<?php echo h($student_number); ?> (<?php echo h($department); ?>) さん！
                <a href="logout.php">ログアウト</a> | <a href="register.php">新規ユーザー登録</a>
            </p>
        </div>

        <h1>時間割作成</h1>

        <div class="main-content">
            <div class="class-list-section">
                <h2>授業リスト</h2>
                <div class="class-filter">
                    <label for="filterGrade">学年:</label>
                    <select id="filterGrade">
                        <option value="">全て</option>
                        <option value="1">1年生</option>
                        <option value="2">2年生</option>
                        <option value="3">3年生</option>
                        <option value="4">4年生</option>
                    </select>

                    <label for="filterTerm">学期:</label>
                    <select id="filterTerm">
                        <option value="">全て</option>
                        <option value="前期">前期</option>
                        <option value="後期">後期</option>
                    </select>

                    <label for="filterCategory">区分:</label>
                    <select id="filterCategory">
                        <option value="">全て</option>
                        </select>
                </div>
                <div class="class-list" id="classList">
                    <p style="text-align: center; color: #777; padding: 20px;">授業を読み込み中...</p>
                </div>
            </div>

            <div class="my-timetable-section">
                <h2>私の時間割</h2>
                <div class="total-credits">登録合計単位数: <span id="totalCredits">0</span>単位</div>

                <div class="timetable-controls">
                    <label for="timetableGradeSelect">表示する時間割を選択:</label>
                    <select id="timetableGradeSelect">
                        <?php
                        // 사용자가 저장한 모든 학년을 옵션으로 표시
                        if (!empty($grades)) {
                            foreach ($grades as $g) {
                                echo '<option value="' . h($g) . '"' . ($g == $initial_grade ? ' selected' : '') . '>' . h($g) . '年生</option>';
                            }
                        } else {
                            echo '<option value="1" selected>1年生</option>'; // 기본값
                        }
                        ?>
                    </select>

                    <label for="timetableTermSelect">学期:</label>
                    <select id="timetableTermSelect">
                        <?php
                        // 사용자가 저장한 모든 학기를 옵션으로 표시
                        if (!empty($terms)) {
                            foreach ($terms as $t) {
                                echo '<option value="' . h($t) . '"' . ($t == $initial_term ? ' selected' : '') . '>' . h($t) . '</option>';
                            }
                        } else {
                            echo '<option value="前期" selected>前期</option>'; // 기본값
                        }
                        ?>
                    </select>
                </div>

                <table class="timetable-table" id="myTimetable">
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
                        $days = ['月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];
                        $periods = range(1, 10); // 1교시부터 10교시까지

                        foreach ($periods as $period) {
                            echo '<tr>';
                            echo '<td class="period-header-cell">' . h($period) . '限<br><span class="period-time">' . ($period + 8) . ':00-' . ($period + 9) . ':00</span></td>'; // 시간정보는 예시
                            foreach ($days as $day) {
                                // data-day와 data-period 속성을 추가
                                echo '<td class="time-slot" data-day="' . h($day) . '" data-period="' . h($period) . '"></td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <div class="timetable-actions">
                    <button id="saveTimetableBtn">時間割を保存</button>
                    <button id="viewConfirmedTimetableBtn">確定済み時間割を見る</button>
                    <button id="checkCreditsBtn">単位取得状況を確認</button>
                </div>
            </div>
        </div>
    </div>

    <script src="main_script.js"></script>
</body>
</html>