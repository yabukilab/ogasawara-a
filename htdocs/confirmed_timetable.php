<?php
session_start(); // 세션 시작
require_once 'db.php'; // 데이터베이스 연결 (여기서 $db 객체가 생성됨)

// h() 함수는 db.php에 정의되어 있으므로 여기서는 제거하거나,
// db.php에 h() 함수 정의가 없다면 이곳에 추가해야 합니다.
// 제공해주신 db.php에는 h() 함수가 이미 정의되어 있으므로 여기서는 중복 정의를 피하기 위해 제거합니다.
// if (!function_exists('h')) {
//     function h($str) {
//         return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
//     }
// }

// 로그인 여부 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // 로그인 페이지로 리다이렉트
    exit;
}

$user_id = $_SESSION['user_id'];
$student_number = $_SESSION['student_number'] ?? 'ゲスト'; // 게스트 (Guest)
$department = $_SESSION['department'] ?? '';

$timetableData = []; // 시간표 데이터를 저장할 배열
$grades = []; // 사용자가 저장한 시간표의 모든 학년 목록
$terms = []; // 사용자가 저장한 시간표의 모든 학기 목록

// $db 객체가 유효한지 확인
if (!isset($db) || !($db instanceof PDO)) {
    // db.php에서 연결 실패 시 $db가 생성되지 않거나 PDO 객체가 아닐 수 있음
    echo '<p style="color: red;">データベース接続エラーが発生しました。管理者に連絡してください。</p>';
    error_log("データベース接続オブジェクト (\$db) が無効です。confirmed_timetable.php");
    exit(); // 스크립트 종료
}


// 사용자가 저장한 시간표의 모든 고유 학년과 학기를 가져옵니다.
try {
    // 모든 학년 가져오기
    $stmt_grades = $db->prepare("SELECT DISTINCT timetable_grade FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_grade ASC");
    $stmt_grades->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_grades->execute();
    $grades = $stmt_grades->fetchAll(PDO::FETCH_COLUMN);

    // 모든 학기 가져오기 (이 부분은 보통 '前期', '後期' 등으로 고정되어 있지만, DB에서 가져오는 것이 유연)
    $stmt_terms = $db->prepare("SELECT DISTINCT timetable_term FROM user_timetables WHERE user_id = :user_id ORDER BY timetable_term ASC");
    $stmt_terms->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt_terms->execute();
    $terms = $stmt_terms->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    // 오류 처리
    error_log("Failed to fetch distinct grades/terms for user {$user_id}: " . $e->getMessage());
    // 사용자에게는 일반적인 오류 메시지 표시
    $grades = [];
    $terms = [];
}

// 선택된 학년과 학기 (기본값 설정)
// 사용자가 선택한 값이 있다면 그것을 사용하고, 없다면 첫 번째 저장된 학년/학기를 기본값으로 설정합니다.
$selected_grade = $_GET['grade'] ?? ($grades[0] ?? null);
$selected_term = $_GET['term'] ?? ($terms[0] ?? null);

// 선택된 학년과 학기에 해당하는 시간표 데이터 불러오기
if ($selected_grade && $selected_term) {
    try {
        $stmt = $db->prepare("
            SELECT ut.class_id, ut.day, ut.period,
                   c.name AS class_name, c.credit, c.category1 AS category_name
            FROM user_timetables ut
            JOIN class c ON ut.class_id = c.id
            WHERE ut.user_id = :user_id
              AND ut.timetable_grade = :grade
              AND ut.timetable_term = :term
            ORDER BY ut.period ASC,
                     FIELD(ut.day, '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日') ASC
        ");
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':grade', $selected_grade, PDO::PARAM_INT);
        $stmt->bindParam(':term', $selected_term, PDO::PARAM_STR);
        $stmt->execute();
        $rawTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 시간표 데이터를 '요일_교시' 형태로 정리하여 HTML에 쉽게 삽입하도록 변환
        foreach ($rawTimetableData as $item) {
            $key = $item['day'] . '_' . $item['period'];
            $timetableData[$key] = $item;
        }

    } catch (PDOException $e) {
        error_log("Failed to load confirmed timetable for user {$user_id}: " . $e->getMessage());
        $timetableData = []; // 오류 발생 시 데이터 초기화
    }
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
        /* confirmed_timetable.php 전용 스타일 (필요하다면 추가) */
        .container {
            max-width: 1000px; /* 時間표만 보여주므로 너비를 조정할 수 있습니다. */
        }
        .timetable-section h2 {
            margin-bottom: 20px;
        }
        .timetable-selection {
            margin-bottom: 20px;
            text-align: center;
        }
        .back-to-creation {
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .back-to-creation a {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        .back-to-creation a:hover {
            background-color: #0056b3;
        }
        /* index.php의 .class-item-in-cell 스타일이 confirmed_timetable.php에서도 동일하게 적용되도록 */
        .confirmed-class-item {
            /* .class-item-in-cell과 동일한 스타일을 적용하거나, .class-item-in-cell을 직접 사용 */
            background-color: #d4edda; /* filled-primary와 유사한 색상 */
            border: 1px solid #28a745;
            padding: 5px;
            font-size: 0.8em;
            border-radius: 3px;
            text-align: center;
            box-sizing: border-box;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        .confirmed-class-item .class-name-in-cell,
        .confirmed-class-item .class-credit-in-cell,
        .confirmed-class-item .category-display-in-cell {
            margin: 0;
            word-break: break-word;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.2em;
            max-height: 2.4em;
        }
        .confirmed-class-item .class-credit-in-cell,
        .confirmed-class-item .category-display-in-cell {
            font-size: 0.8em;
            color: #555;
            white-space: nowrap; /* 学点/学년은 한 줄에 표시 */
        }
        /* confirmed_timetable.php에서는 삭제 버튼이 필요 없으므로 숨김 */
        .confirmed-class-item .remove-button {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <p>ようこそ、<?php echo h($student_number); ?> (<?php echo h($department); ?>) さん！
                <a href="logout.php">ログアウト</a>
            </p>
        </div>

        <h1>確定済み時間割</h1>

        <div class="timetable-section">
            <div class="timetable-selection">
                <form method="GET" action="confirmed_timetable.php">
                    <label for="gradeSelect">表示する学年:</label>
                    <select id="gradeSelect" name="grade" onchange="this.form.submit()">
                        <?php if (empty($grades)): ?>
                            <option value="">保存された時間割がありません</option>
                        <?php else: ?>
                            <?php foreach ($grades as $grade_option): ?>
                                <option value="<?php echo h($grade_option); ?>"
                                    <?php echo ($grade_option == $selected_grade) ? 'selected' : ''; ?>>
                                    <?php echo h($grade_option); ?>年生
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>

                    <label for="termSelect" style="margin-left: 10px;">学期:</label>
                    <select id="termSelect" name="term" onchange="this.form.submit()">
                        <?php if (empty($terms)): ?>
                            <option value="">保存された時間割がありません</option>
                        <?php else: ?>
                            <?php foreach ($terms as $term_option): ?>
                                <option value="<?php echo h($term_option); ?>"
                                    <?php echo ($term_option == $selected_term) ? 'selected' : ''; ?>>
                                    <?php echo h($term_option); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <?php if (empty($grades) || empty($terms) || empty($timetableData)): ?>
                <p style="text-align: center; margin-top: 30px; font-size: 1.1em; color: #777;">
                    選択された学期・学年の時間割はまだ保存されていません。
                </p>
            <?php else: ?>
                <table class="timetable-table">
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
                            echo '<td class="period-header-cell">' . h($period) . '限<br><span class="period-time">' . ($period + 8) . ':00-' . ($period + 9) . ':00</span></td>';
                            foreach ($days as $day) {
                                $key = $day . '_' . $period;
                                $class = $timetableData[$key] ?? null; // 해당 요일/교시에 수업이 있는지 확인
                                echo '<td class="time-slot">';
                                if ($class) {
                                    echo '<div class="class-item-in-cell">'; // index.php와 동일한 CSS 클래스 사용
                                    echo '<div class="class-name-in-cell">' . h($class['class_name']) . '</div>';
                                    echo '<div class="class-credit-in-cell">' . h($class['credit']) . '単位</div>';
                                    echo '<div class="category-display-in-cell">' . h($class['category_name']) . '</div>';
                                    echo '</div>';
                                }
                                echo '</td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div class="back-to-creation">
                <a href="index.php">時間割作成に戻る</a>
            </div>
        </div>
    </div>
</body>
</html>