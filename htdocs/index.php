<?php
session_start();
// db_config.php 파일이 데이터베이스 연결을 설정하고 $db 변수를 제공한다고 가정합니다.
require_once 'db_config.php';

<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 나머지 index.php 코드...
?>

// 로그인 상태 확인 및 사용자 정보 가져오기
$isUserLoggedIn = isset($_SESSION['user_id']);
$loggedInUserId = $isUserLoggedIn ? $_SESSION['user_id'] : null;
$loggedInStudentNumber = $isUserLoggedIn ? $_SESSION['student_number'] : 'ゲスト'; // 학번 또는 이름 등
$loggedInDepartment = $isUserLoggedIn ? $_SESSION['department'] : '情報なし'; // 학과 정보

// 학년 및 학기 필터 초기값 설정
$currentSelectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 1;
$currentSelectedTerm = isset($_GET['term_filter']) ? (string)$_GET['term_filter'] : '0'; // '0'은 '全て'를 의미

$message = '';
$class_list = [];
$initialTimetableData = []; // JavaScript로 전달할 초기 시간표 데이터

try {
    // 수업 목록 가져오기 (필터 적용)
    $sql_classes = "SELECT id, name, credit, term, grade, category1, category2, category3 FROM class WHERE 1=1";
    $params_classes = [];

    if ($currentSelectedGrade > 0) {
        $sql_classes .= " AND grade = :grade";
        $params_classes[':grade'] = $currentSelectedGrade;
    }

    if ($currentSelectedTerm !== '0') {
        $sql_classes .= " AND term = :term";
        $params_classes[':term'] = $currentSelectedTerm;
    }

    $stmt_classes = $db->prepare($sql_classes);
    $stmt_classes->execute($params_classes);
    $class_list = $stmt_classes->fetchAll(PDO::FETCH_ASSOC);

    // 로그인된 사용자이고, 현재 학년에 해당하는 저장된 시간표 데이터가 있다면 가져오기
    if ($isUserLoggedIn) {
        $sql_timetable = "
            SELECT
                ut.day,
                ut.period,
                c.id AS class_id,
                c.name AS className,
                c.credit AS classCredit,
                c.term AS classTerm,
                c.grade AS classGrade
                -- 추가적으로 시간표 칸에 표시할 다른 정보가 있다면 여기 추가
                -- 예: c.category1 AS classCategory1, c.category2 AS classCategory2
            FROM
                user_timetables ut
            JOIN
                class c ON ut.class_id = c.id
            WHERE
                ut.user_id = :user_id AND ut.grade = :grade_filter
            ORDER BY ut.day, ut.period
        ";
        $stmt_timetable = $db->prepare($sql_timetable);
        $stmt_timetable->execute([':user_id' => $loggedInUserId, ':grade_filter' => $currentSelectedGrade]);
        $initialTimetableData = $stmt_timetable->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $message = '<p class="message error">データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log("DB Error on index.php: " . $e->getMessage()); // 에러 로깅
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成 (Timetable Creator)</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
</head>
<body>
    <div class="user-info">
        <?php if ($isUserLoggedIn): ?>
            <p>ようこそ、<?php echo htmlspecialchars($loggedInStudentNumber); ?>さん (<?php echo htmlspecialchars($loggedInDepartment); ?>)</p>
            <a href="logout.php">ログアウト</a>
        <?php else: ?>
            <p>ゲストとして閲覧中</p>
            <a href="login.php">ログイン</a>
            <a href="register_user.php">新規ユーザー登録</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <h1>時間割作成</h1>

        <?php echo $message; ?>

        <div class="filter-form">
            <form action="index.php" method="get" id="filterForm">
                <label for="grade_filter">学年:</label>
                <select id="grade_filter" name="grade_filter" onchange="document.getElementById('filterForm').submit();">
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($currentSelectedGrade == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?>年生
                        </option>
                    <?php endfor; ?>
                </select>

                <label for="term_filter">学期:</label>
                <select id="term_filter" name="term_filter" onchange="document.getElementById('filterForm').submit();">
                    <option value="0" <?php echo ($currentSelectedTerm == '0') ? 'selected' : ''; ?>>全て</option>
                    <option value="1" <?php echo ($currentSelectedTerm == '1') ? 'selected' : ''; ?>>前期</option>
                    <option value="2" <?php echo ($currentSelectedTerm == '2') ? 'selected' : ''; ?>>後期</option>
                </select>
            </form>

            <div class="current-filters">
                現在の表示: <span id="displayGrade"></span> <span id="displayTerm"></span>
            </div>
        </div>


        <div class="main-container">
            <div class="class-list-section">
                <h2>授業選択</h2>
                <div id="selectedClassInfo">
                    <p>選択中の授業: <span id="currentSelectedClassName">なし</span></p>
                    <p>単位: <span id="currentSelectedClassCredit">0</span></p>
                    <button onclick="addClassToTimetable()">時間割に追加</button>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>学年</th>
                            <th>学期</th>
                            <th>授業名</th>
                            <th>単位</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($class_list)): ?>
                            <tr><td colspan="5">該当する授業がありません。</td></tr>
                        <?php else: ?>
                            <?php foreach ($class_list as $class): ?>
                                <tr data-class-id="<?php echo htmlspecialchars($class['id']); ?>"
                                    data-class-name="<?php echo htmlspecialchars($class['name']); ?>"
                                    data-class-credit="<?php echo htmlspecialchars($class['credit']); ?>"
                                    data-class-term="<?php echo htmlspecialchars($class['term']); ?>"
                                    data-class-grade="<?php echo htmlspecialchars($class['grade']); ?>">
                                    <td><?php echo htmlspecialchars($class['grade']); ?></td>
                                    <td>
                                        <?php
                                            // 학기 숫자를 문자로 변환
                                            if ($class['term'] == 1) {
                                                echo '前期';
                                            } elseif ($class['term'] == 2) {
                                                echo '後期';
                                            } else {
                                                echo '不明'; // 또는 적절한 기본값
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['credit']); ?></td>
                                    <td><button class="add-button" onclick="selectClass(this)">選択</button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

                 <div class="timetable-section">
                <h2>あなたの時間割</h2>
                <p id="totalCredits">合計単位数: 0</p>
                <div class="timetable-controls">
                    <label for="day_select">曜日:</label>
                    <select id="day_select">
                        <option value="月">月曜日</option>
                        <option value="火">火曜日</option>
                        <option value="水">水曜日</option>
                        <option value="木">木曜日</option>
                        <option value="金">金曜日</option>
                        <option value="土">土曜日</option>
                    </select>
                    <label for="time_select">時限:</label>
                    <select id="time_select">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?>限</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <table class="timetable-table" id="timetable">
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
                        <?php for ($period = 1; $period <= 10; $period++): ?>
                            <tr>
                                <td><?php echo $period; ?></td>
                                <?php foreach (['月', '火', '水', '木', '金', '土'] as $day): ?>
                                    <td id="cell-<?php echo $day; ?>-<?php echo $period; ?>" class="time-slot">
                                        </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>

                <?php if ($isUserLoggedIn): ?>
                    <button id="confirmTimetableBtn" onclick="confirmTimetable()">この時間割で登録確定</button>
                    <a href="confirmed_timetable.php?grade_filter=<?php echo $currentSelectedGrade; ?>" class="view-confirmed-button">確定済み時間割を見る</a>
                <?php else: ?>
                    <button id="confirmTimetableBtn" class="disabled-button" disabled>ログインして時間割を保存</button>
                    <p style="text-align: center; margin-top: 10px;">時間割を保存するには<a href="login.php">ログイン</a>してください。</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // PHP에서 JavaScript로 변수 전달
        const currentSelectedGradeFromPHP = <?php echo json_encode($currentSelectedGrade); ?>;
        const currentSelectedTermFromPHP = <?php echo json_encode($currentSelectedTerm); ?>;
        const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;
        const currentLoggedInUserId = <?php echo json_encode($loggedInUserId); ?>;
        const initialTimetableData = <?php echo json_encode($initialTimetableData); ?>; // 초기 시간표 데이터

        // 초기화 함수 호출은 script.js에 정의되어 있을 것입니다.
        // DOMContentLoaded를 사용하여 스크립트가 로드된 후 실행되도록 합니다.
        document.addEventListener('DOMContentLoaded', function() {
            // script.js에 정의된 함수들을 호출
            initializeTimetableFromPHP(initialTimetableData); // 저장된 시간표를 로드
            updateFilterDisplay(); // 현재 필터 상태를 표시
        });
    </script>
    <script src="script.js"></script>
</body>
</html>