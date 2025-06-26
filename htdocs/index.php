<?php
session_start();
require_once 'db_config.php'; // DB 설정 및 getTermName 함수 포함

// 로그인 확인 - student_number로 로그인 여부 확인
$is_logged_in = isset($_SESSION['student_number']);
$current_student_number = $is_logged_in ? $_SESSION['student_number'] : 'ゲスト'; // 게스트
$current_user_department = '未設定'; // 미설정

if ($is_logged_in) {
    try {
        $stmt = $pdo->prepare("SELECT department FROM users WHERE student_number = :student_number");
        $stmt->execute([':student_number' => $current_student_number]);
        $user_info = $stmt->fetch();
        if ($user_info) {
            $current_user_department = htmlspecialchars($user_info['department']);
        }
    } catch (PDOException $e) {
        error_log("Failed to load user department: " . $e->getMessage());
    }
}

// ユーザーがログインしていない場合、時間割機能の代わりにログイン/登録を促すメッセージを表示
if (!$is_logged_in) {
    echo '<!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>授業登録 (Class Registration)</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
        <div class="auth-container">
            <h1>時間割登録システム</h1>
            <p>時間割を編集・保存するには、ログインまたは新規ユーザー登録が必要です。</p>
            <div class="auth-links">
                <a href="login.php">ログイン</a>
                <a href="register_user.php">新規ユーザー登録</a>
            </div>
            <p><a href="index.php">ゲストとして時間割を見る (ゲストログイン)</a></p>
        </div>
    </body>
    </html>';
    exit; // 로그인하지 않은 경우, 여기서 스크립트 실행을 중단
}

// --- 로그인된 사용자 또는 게스트를 위한 시간표 등록 기능 시작 ---

$selectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2; // 기본 2학년
$selectedTermFilter = isset($_GET['term_filter']) ? $_GET['term_filter'] : '0'; // 기본 전체 학기

// 이용 가능한 수업 목록 가져오기
$classes = [];
try {
    $sql = "SELECT id, grade, term, name, category1, category2, category3, credit FROM class WHERE grade = :grade_filter";
    $params = [':grade_filter' => $selectedGrade];

    if ($selectedTermFilter !== '0') {
        $sql .= " AND term = :term_filter";
        $params[':term_filter'] = (int)$selectedTermFilter;
    }
    $sql .= " ORDER BY name ASC"; // 수업명으로 정렬

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $classFetchError = "授業リストの読み込みに失敗しました: " . htmlspecialchars($e->getMessage());
}

// 사용자별 확정 시간표 데이터 로드 (JSON 파일 대신 DB에서 로드)
$currentTimetableData = [];
if ($is_logged_in) {
    try {
        // SQL 쿼리에서 'ut.is_primary'를 제거
        $stmt = $pdo->prepare("SELECT ut.day, ut.period, ut.class_id,
                                        c.name as className, c.credit as classCredit, c.term as classTerm, c.grade as classGrade
                               FROM user_timetables ut
                               JOIN class c ON ut.class_id = c.id
                               WHERE ut.student_number = :student_number AND ut.grade = :grade");
        $stmt->execute([
            ':student_number' => $current_student_number,
            ':grade' => $selectedGrade
        ]);
        $currentTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // 시간표 로드 에러 처리
        error_log("Failed to load user timetable: " . $e->getMessage()); // 에러 로그
        $currentTimetableData = []; // 에러 시 빈 배열
    }
}


// 시간 시한 정의
$times = [
    1 => '9:00-10:00', 2 => '10:00-11:00', 3 => '11:00-12:00',
    4 => '13:00-14:00', 5 => '14:00-15:00', 6 => '15:00-16:00',
    7 => '16:00-17:00', 8 => '17:00-18:00', 9 => '18:00-19:00', 10 => '19:00-20:00'
];
$days_of_week = ['月', '火', '水', '木', '金', '土']; // 요일 정의
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業登録 (Class Registration)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo $current_user_department; ?>)
        <?php if ($is_logged_in): ?>
            <a href="logout.php">ログアウト</a>
        <?php endif; ?>
    </div>

    <h1>授業登録</h1>

    <?php if ($is_logged_in): // 로그인된 경우에만 버튼 표시 ?>
        <a href="confirmed_timetable.php?grade_filter=<?= htmlspecialchars($selectedGrade) ?>" class="view-confirmed-button">
            確定済み時間割を見る
        </a>
    <?php endif; ?>

    <div class="container main-container">
        <div class="class-list-section">
            <h2>利用可能な授業一覧</h2>

            <form action="index.php" method="get" id="grade_filter_form">
                <label for="grade_filter">学年フィルタ:</label>
                <select name="grade_filter" id="grade_filter">
                    <?php
                    for ($g = 1; $g <= 4; $g++) {
                        echo "<option value='{$g}'" . ($selectedGrade === $g ? ' selected' : '') . ">{$g}年生</option>";
                    }
                    ?>
                </select>
            </form>

            <form action="index.php" method="get" id="term_filter_form">
                <label for="term_filter">学期フィルタ:</label>
                <select name="term_filter" id="term_filter">
                    <option value="0" <?php echo ($selectedTermFilter === '0') ? 'selected' : ''; ?>>全て</option>
                    <option value="1" <?php echo ($selectedTermFilter === '1') ? 'selected' : ''; ?>>前期</option>
                    <option value="2" <?php echo ($selectedTermFilter === '2') ? 'selected' : ''; ?>>後期</option>
                </select>
            </select>
            </form>

            <?php if (isset($classFetchError)): ?>
                <p class="message error"><?php echo $classFetchError; ?></p>
            <?php elseif (empty($classes)): ?>
                <p>利用可能な授業がありません。</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>学年</th><th>学期</th><th>授業名</th><th>単位</th><th>アクション</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr data-class-id="<?= htmlspecialchars($class['id']) ?>"
                                data-class-name="<?= htmlspecialchars($class['name']) ?>"
                                data-class-credit="<?= htmlspecialchars($class['credit']) ?>"
                                data-class-term="<?= htmlspecialchars($class['term']) ?>"
                                data-class-grade="<?= htmlspecialchars($class['grade']) ?>">
                                <td><?= htmlspecialchars($class['grade']) ?>年生</td>
                                <td><?= getTermName($class['term']) ?></td>
                                <td><?= htmlspecialchars($class['name']) ?></td>
                                <td><span class="class-credit"><?= htmlspecialchars($class['credit']) ?></span></td>
                                <td><button class='add-button' onclick='selectClass(this)'>選択</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="timetable-section" id="timetableSection">
            <h2>時間割作成</h2>
            <div id="selectedClassInfo">
                <p>選択中の授業: <span id="currentSelectedClassName">なし</span> (単位: <span id="currentSelectedClassCredit">0</span>)</p>
                <p>選択した授業を時間割に配置してください。</p>
            </div>

            <div style="margin-top: 20px;">
                <label for="day_select">曜日:</label>
                <select id="day_select">
                    <?php foreach ($days_of_week as $day_name): ?>
                        <option value="<?= htmlspecialchars($day_name) ?>"><?= htmlspecialchars($day_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="time_select">時限:</label>
                <select id="time_select">
                    <?php foreach ($times as $period => $time_range): ?>
                        <option value="<?= $period ?>"><?= $period ?>限 (<?= htmlspecialchars($time_range) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button onclick="addClassToTimetable()">時間割に追加</button>
            </div>

            <h3 id="currentTimetableInfo">
                時間割 (現在の学年: <span id="displayGrade"><?= htmlspecialchars($selectedGrade) ?>年生</span>,
                学期: <span id="displayTerm"><?= getTermName((int)$selectedTermFilter) ?></span>)
            </h3>
            <table class="timetable-table" id="timetable">
                <thead>
                    <tr>
                        <th>時限</th>
                        <?php foreach ($days_of_week as $day_name): ?>
                            <th><?= htmlspecialchars($day_name) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($times as $i => $time_range) { // $i는 1부터 10까지의 시한
                        echo "<tr>";
                        echo "<td>" . $i . "限<br><span style='font-size:0.8em; color:#666;'>" . explode('-', $time_range)[0] . "</span></td>"; // 시작 시간만 표시

                        foreach ($days_of_week as $day_name) {
                            $cellContent = '';
                            $cellClasses = 'time-slot';
                            $cellDataAttrs = ''; // No longer need data-is-primary or data-linked-period
                            $termDisplayInCell = '';

                            $foundClass = null;
                            foreach ($currentTimetableData as $classEntry) {
                                if ($classEntry['day'] === $day_name && (int)$classEntry['period'] === $i) {
                                    $foundClass = $classEntry;
                                    break;
                                }
                            }

                            if ($foundClass) {
                                $cellContent = htmlspecialchars($foundClass['className']) . "<br>(" . htmlspecialchars($foundClass['classCredit']) . "単位)";
                                $cellClasses .= ' filled-primary'; // All filled cells are primary in display
                                $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($foundClass['classTerm']) . "</div>";

                                $cellDataAttrs .= " data-class-id='" . htmlspecialchars($foundClass['class_id']) . "'";
                                $cellDataAttrs .= " data-class-name='" . htmlspecialchars($foundClass['className']) . "'";
                                $cellDataAttrs .= " data-class-credit='" . htmlspecialchars($foundClass['classCredit']) . "'";
                                $cellDataAttrs .= " data-class-term='" . htmlspecialchars($foundClass['classTerm']) . "'";
                                $cellDataAttrs .= " data-class-grade='" . htmlspecialchars($foundClass['classGrade']) . "'";
                                // is_primary 관련 data-* 속성은 이제 필요 없음
                                $cellContent .= "<button class='remove-button' onclick='removeClassFromTimetable(this)'>X</button>";
                            }

                            // 셀 출력 (data-day 속성 사용)
                            echo "<td class='{$cellClasses}' data-day='{$day_name}' data-time='{$i}' {$cellDataAttrs}>{$cellContent}{$termDisplayInCell}</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div id="totalCredits">合計単位数: 0</div>
            <button id="confirmTimetableBtn" onclick="confirmTimetable()">この時間割で登録確定</button>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
        // PHP 변수를 JavaScript로 전달
        const currentSelectedGradeFromPHP = <?php echo json_encode($selectedGrade); ?>;
        const currentSelectedTermFromPHP = <?php echo json_encode($selectedTermFilter); ?>; // 학기 필터 값
        const currentLoggedInStudentNumber = <?php echo json_encode($current_student_number); ?>; // 게스트도 포함
        const initialTimetableData = <?php echo json_encode($currentTimetableData); ?>; // 초기 시간표 데이터

        // DOMContentLoaded를 사용하여 모든 요소가 로드된 후 스크립트 실행
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimetableFromPHP(initialTimetableData); // PHP에서 로드한 데이터로 시간표 초기화
            updateFilterDisplay(); // 초기 로드 시 필터 표시 업데이트
            updateDisplayTotalCredits(); // 초기 로드 후 총 학점 다시 계산 및 표시

            // 필터 드롭다운 변경 시 자동으로 폼 제출
            document.getElementById('grade_filter').addEventListener('change', function() {
                window.location.href = `index.php?grade_filter=${this.value}&term_filter=${document.getElementById('term_filter').value}`;
            });

            document.getElementById('term_filter').addEventListener('change', function() {
                window.location.href = `index.php?grade_filter=${document.getElementById('grade_filter').value}&term_filter=${this.value}`;
            });
        });
    </script>
</body>
</html>