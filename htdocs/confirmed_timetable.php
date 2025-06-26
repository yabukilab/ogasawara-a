<?php
session_start();
require_once 'db_config.php'; // DB 설정 및 getTermName 함수 포함

// 로그인 확인
if (!isset($_SESSION['student_number'])) {
    // 로그인되지 않은 경우 로그인 페이지로 리다이렉트
    header('Location: login.php');
    exit;
}

$current_student_number = $_SESSION['student_number'];
$selectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2; // 기본 2학년

// 사용자 학과 정보 로드 (선택 사항)
$current_user_department = '未設定';
try {
    $stmt = $pdo->prepare("SELECT department FROM users WHERE student_number = :student_number");
    $stmt->execute([':student_number' => $current_student_number]);
    $user_info = $stmt->fetch();
    if ($user_info) {
        $current_user_department = htmlspecialchars($user_info['department']);
    }
} catch (PDOException $e) {
    error_log("Failed to load user department in confirmed_timetable: " . $e->getMessage());
}

// 확정된 시간표 데이터 로드
$confirmedTimetableData = [];
try {
    $stmt = $pdo->prepare("SELECT ut.day, ut.period, ut.class_id,
                                    c.name as className, c.credit as classCredit, c.term as classTerm, c.grade as classGrade
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.student_number = :student_number AND ut.grade = :grade
                           ORDER BY ut.day, ut.period"); // 요일, 시한 순으로 정렬
    $stmt->execute([
        ':student_number' => $current_student_number,
        ':grade' => $selectedGrade
    ]);
    $confirmedTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Failed to load confirmed timetable: " . $e->getMessage());
    $confirmedTimetableData = [];
    $fetchError = "確定済み時間割の読み込みに失敗しました。";
}

// 시간 시한 정의
$times = [
    1 => '9:00-10:00', 2 => '10:00-11:00', 3 => '11:00-12:00',
    4 => '13:00-14:00', 5 => '14:00-15:00', 6 => '15:00-16:00',
    7 => '16:00-17:00', 8 => '17:00-18:00', 9 => '18:00-19:00', 10 => '19:00-20:00'
];
$days_of_week = ['月', '火', '水', '木', '金', '土']; // 요일 정의

// 총 학점 계산
$totalCredits = 0;
$calculatedClassIds = []; // 중복 학점 계산 방지
foreach ($confirmedTimetableData as $entry) {
    if (!in_array($entry['class_id'], $calculatedClassIds)) {
        $totalCredits += (int)$entry['classCredit'];
        $calculatedClassIds[] = $entry['class_id'];
    }
}

// 시간표 데이터를 맵 형태로 변환하여 쉽게 접근
$timetableMap = [];
foreach ($confirmedTimetableData as $entry) {
    if (!isset($timetableMap[$entry['day']])) {
        $timetableMap[$entry['day']] = [];
    }
    $timetableMap[$entry['day']][(int)$entry['period']] = $entry;
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確定済み時間割 (Confirmed Timetable)</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .navigation-buttons {
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .navigation-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #28a745; /* 녹색 계열 */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none;
            margin-right: 10px;
        }
        .navigation-buttons a:hover {
            background-color: #218838;
        }
        .filter-form {
            margin-bottom: 20px;
        }
        .filter-form label, .filter-form select {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo $current_user_department; ?>)
        <a href="logout.php">ログアウト</a>
    </div>

    <h1>確定済み時間割</h1>

    <div class="navigation-buttons">
        <a href="index.php?grade_filter=<?= htmlspecialchars($selectedGrade) ?>">時間割を編集</a>
    </div>

    <div class="filter-form">
        <form action="confirmed_timetable.php" method="get">
            <label for="grade_filter">表示学年:</label>
            <select name="grade_filter" id="grade_filter" onchange="this.form.submit()">
                <?php
                for ($g = 1; $g <= 4; $g++) {
                    echo "<option value='{$g}'" . ($selectedGrade === $g ? ' selected' : '') . ">{$g}年生</option>";
                }
                ?>
            </select>
        </form>
    </div>

    <?php if (isset($fetchError)): ?>
        <p class="message error"><?php echo $fetchError; ?></p>
    <?php elseif (empty($confirmedTimetableData)): ?>
        <p>この学年の確定済み時間割はありません。</p>
    <?php else: ?>
        <table class="timetable-table">
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
                        $termDisplayInCell = '';

                        $classEntry = $timetableMap[$day_name][$i] ?? null;

                        if ($classEntry) {
                            $cellContent = htmlspecialchars($classEntry['className']) . "<br>(" . htmlspecialchars($classEntry['classCredit']) . "単位)";
                            $cellClasses .= ' filled-primary'; // 확정된 시간표에서는 모두 채워진 칸
                            $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($classEntry['classTerm']) . "</div>";
                        }
                        echo "<td class='{$cellClasses}'>{$cellContent}{$termDisplayInCell}</td>";
                    }
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
        <div id="totalCredits">合計単位数: <?= htmlspecialchars($totalCredits) ?></div>
    <?php endif; ?>
</body>
</html>