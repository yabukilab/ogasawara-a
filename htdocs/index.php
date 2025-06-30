<?php
session_start();
require_once 'db_config.php'; // DB 설정 파일 포함

// 로그인 상태 확인
$isUserLoggedIn = isset($_SESSION['user_id']);
$loggedInUserId = $isUserLoggedIn ? $_SESSION['user_id'] : null;
$loggedInStudentNumber = $isUserLoggedIn ? $_SESSION['student_number'] : 'ゲスト';
$loggedInDepartment = $isUserLoggedIn ? $_SESSION['department'] : '情報なし';

// 학년 및 학기 필터 초기값 설정
// GET 요청으로 grade_filter가 넘어오면 그 값을 사용하고, 없으면 기본값 1학년으로 설정
$currentSelectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 1;
// GET 요청으로 term_filter가 넘어오면 그 값을 사용하고, 없으면 기본값 '0' (모두)으로 설정
$currentSelectedTerm = isset($_GET['term_filter']) ? (string)$_GET['term_filter'] : '0'; // 0은 '全て'를 의미

$message = '';
$class_list = [];
$initialTimetableData = []; // JavaScript로 넘겨줄 초기 시간표 데이터

try {
    // 수업 목록 가져오기 (필터 적용)
    $sql = "SELECT id, name, credit, term, grade FROM class WHERE 1=1";
    $params = [];

    // 학년 필터 적용
    if ($currentSelectedGrade > 0) {
        $sql .= " AND grade = :grade";
        $params[':grade'] = $currentSelectedGrade;
    }

    // 학기 필터 적용 ('0'은 모든 학기, 1은 전기, 2는 후기)
    if ($currentSelectedTerm !== '0') {
        $sql .= " AND term = :term";
        $params[':term'] = $currentSelectedTerm;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $class_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 로그인된 사용자이고, 해당 학년에 저장된 시간표 데이터가 있다면 가져오기
    if ($isUserLoggedIn) {
        // user_timetables와 class 테이블을 조인하여 수업명, 학점 등 상세 정보도 함께 가져옴
        $stmt = $db->prepare("
            SELECT
                ut.day,
                ut.period,
                c.id AS class_id,
                c.name AS className,
                c.credit AS classCredit,
                c.term AS classTerm,
                c.grade AS classGrade
            FROM
                user_timetables ut
            JOIN
                class c ON ut.class_id = c.id
            WHERE
                ut.user_id = :user_id AND ut.grade = :grade_filter
            ORDER BY ut.day, ut.period
        ");
        $stmt->execute([':user_id' => $loggedInUserId, ':grade_filter' => $currentSelectedGrade]);
        $initialTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $message = '<p class="message error">データベースエラー: ' . htmlspecialchars($e->getMessage()) . '</p>';
    error_log("DB Error on index.php: " . $e->getMessage());
}

// PHP 변수를 JavaScript로 전달
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成 (Timetable Creator)</title>
    <link rel="stylesheet" href="style.css">
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
        // PHPからJavaScript로 변수 전달
        const currentSelectedGradeFromPHP = <?php echo json_encode($currentSelectedGrade); ?>;
        const currentSelectedTermFromPHP = <?php echo json_encode($currentSelectedTerm); ?>;
        const isUserLoggedIn = <?php echo json_encode($isUserLoggedIn); ?>;
        const currentLoggedInUserId = <?php echo json_encode($loggedInUserId); ?>;
        const initialTimetableData = <?php echo json_encode($initialTimetableData); ?>;

        // 초기화 함수 호출
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimetableFromPHP(initialTimetableData);
            updateFilterDisplay(); // 현재 필터 상태 표시
        });
    </script>
    <script src="script.js"></script>
</body>
</html>