<?php
session_start();
require_once 'db_config.php'; // DB 設定と h()、getTermName() 関数を含む

// ログイン確認
if (!isset($_SESSION['student_number']) || !isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_student_number = $_SESSION['student_number'];
$current_user_id = $_SESSION['user_id'];
$current_user_department = $_SESSION['department']; // 学科情報もセッションから取得

// エラーメッセージと成功メッセージ用変数
$message = '';
$message_type = '';

// 現在選択されている学年 (URLパラメータから取得、デフォルトは2年生)
$current_grade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2;
// 現在選択されている期間 (URLパラメータから取得、デフォルトは'通年')
$current_term = isset($_GET['term_filter']) ? $_GET['term_filter'] : '通年';


// クラス情報取得
$classes = [];
try {
    $sql = "SELECT id, class_name, grade, term, day_of_week, time_slot, credit, category1, category2 FROM class WHERE grade = :grade";
    $params = [':grade' => $current_grade];

    if ($current_term !== '全て') {
        $sql .= " AND term = :term";
        $params[':term'] = $current_term;
    }
    // プロジェクトマネジメント学科の授業のみをフィルター
    $sql .= " AND department = :department ORDER BY term, day_of_week, time_slot";
    $params[':department'] = $current_user_department; // 現在ログイン中のユーザーの学科でフィルター

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("授業情報の読み込みに失敗しました: " . $e->getMessage());
    $message = "授業情報の読み込みに失敗しました。";
    $message_type = 'error';
}

// ユーザーの時間割データ取得
$user_timetable = [];
$total_current_credits = 0; // 現在の学期の履修単位数

try {
    // ユーザーの時間割を class テーブルと結合して詳細情報を取得
    $stmt = $db->prepare("SELECT ut.id as user_timetable_id, c.id as class_id, c.class_name, c.credit, c.day_of_week, c.time_slot, c.term, c.grade
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.user_id = :user_id AND c.grade = :grade AND (c.term = :current_term OR :current_term = '全て')");

    // '全て'を選択した場合、termフィルタリングを無効にする
    $term_param = ($current_term === '全て') ? '%' : $current_term;

    $stmt->execute([':user_id' => $current_user_id, ':grade' => $current_grade, ':current_term' => $term_param]);
    $user_registered_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($user_registered_classes as $class) {
        $user_timetable[] = $class;
        $total_current_credits += (int)$class['credit'];
    }
} catch (PDOException $e) {
    error_log("時間割情報の読み込みに失敗しました: " . $e->getMessage());
    $message = "時間割情報の読み込みに失敗しました。";
    $message_type = 'error';
}


// 時間割への追加処理 (AJAXで呼ばれることを想定、ここでは仮の直接処理)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $class_id = filter_input(INPUT_POST, 'class_id', FILTER_VALIDATE_INT);
        $day_of_week = h($_POST['day_of_week']);
        $time_slot = h($_POST['time_slot']);
        $term = h($_POST['term']); // 期間情報も取得

        if ($class_id && $day_of_week && $time_slot && $term) {
            try {
                // 同じユーザー、同じ時間、同じ曜日に既に登録된 수업이 있는지 확인
                $stmt = $db->prepare("SELECT COUNT(*) FROM user_timetables ut JOIN class c ON ut.class_id = c.id WHERE ut.user_id = :user_id AND c.day_of_week = :day_of_week AND c.time_slot = :time_slot AND c.term = :term AND c.grade = :grade");
                $stmt->execute([
                    ':user_id' => $current_user_id,
                    ':day_of_week' => $day_of_week,
                    ':time_slot' => $time_slot,
                    ':term' => $term,
                    ':grade' => $current_grade // 현재 학년의 시간표에만 추가하도록 제한
                ]);
                if ($stmt->fetchColumn() > 0) {
                    $message = "指定された時間帯には既に授業が登録されています。";
                    $message_type = 'error';
                } else {
                    // user_timetables에 추가
                    $stmt = $db->prepare("INSERT INTO user_timetables (user_id, class_id, is_confirmed) VALUES (:user_id, :class_id, 0)");
                    $stmt->execute([':user_id' => $current_user_id, ':class_id' => $class_id]);
                    $message = "授業が時間割に追加されました！";
                    $message_type = 'success';
                    // ページをリロードして最新の時間割を表示
                    header("Location: index.php?grade_filter={$current_grade}&term_filter={$current_term}&message=" . urlencode($message) . "&message_type=" . $message_type);
                    exit;
                }
            } catch (PDOException $e) {
                error_log("時間割への追加に失敗しました: " . $e->getMessage());
                $message = "授業を時間割に追加できませんでした。";
                $message_type = 'error';
            }
        } else {
            $message = "授業の追加に必要な情報が不足しています。";
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'remove') {
        $user_timetable_id = filter_input(INPUT_POST, 'user_timetable_id', FILTER_VALIDATE_INT);

        if ($user_timetable_id) {
            try {
                // user_timetables から削除
                $stmt = $db->prepare("DELETE FROM user_timetables WHERE id = :id AND user_id = :user_id");
                $stmt->execute([':id' => $user_timetable_id, ':user_id' => $current_user_id]);
                $message = "授業が時間割から削除されました。";
                $message_type = 'success';
                // ページをリロード
                header("Location: index.php?grade_filter={$current_grade}&term_filter={$current_term}&message=" . urlencode($message) . "&message_type=" . $message_type);
                exit;
            } catch (PDOException $e) {
                error_log("時間割からの削除に失敗しました: " . $e->getMessage());
                $message = "授業を時間割から削除できませんでした。";
                $message_type = 'error';
            }
        } else {
            $message = "削除する授業の情報が不足しています。";
            $message_type = 'error';
        }
    } elseif ($_POST['action'] === 'confirm_timetable') {
        try {
            // is_confirmed を 1 に更新
            $stmt = $db->prepare("UPDATE user_timetables SET is_confirmed = 1 WHERE user_id = :user_id AND is_confirmed = 0 AND grade = :grade AND (term = :term OR :term = '全て')");
            $stmt->execute([':user_id' => $current_user_id, ':grade' => $current_grade, ':term' => $term_param]); // $term_paramを使用
            $message = "時間割が確定されました！";
            $message_type = 'success';
            header("Location: confirmed_timetable.php?grade_filter={$current_grade}&term_filter={$current_term}&message=" . urlencode($message) . "&message_type=" . $message_type);
            exit;
        } catch (PDOException $e) {
            error_log("時間割の確定に失敗しました: " . $e->getMessage());
            $message = "時間割を確定できませんでした。";
            $message_type = 'error';
        }
    }
}


// URLパラメータからのメッセージ表示
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = h($_GET['message']);
    $message_type = h($_GET['message_type']);
}

// 期間名を取得するヘル퍼関数 (db_config.php に定義されているはず)
// function getTermName($termValue) { ... }

// HTML特殊文字をエスケープするヘルパー関数 (db_config.php に定義されているはず)
// function h($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); }

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>時間割作成 (Timetable Creation)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo htmlspecialchars($current_user_department); ?>)
        <a href="logout.php">ログアウト</a>
    </div>

    <div class="container">
        <h1>時間割作成</h1>

        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo h($message); ?></p>
        <?php endif; ?>

        <div class="navigation-buttons">
            <a href="confirmed_timetable.php?grade_filter=<?= $current_grade ?>&term_filter=<?= $current_term ?>">確定済み時間割を見る</a>
            <a href="credits_status.php">単位取得状況を確認</a>
        </div>

        <div class="main-container">
            <div class="class-list-section">
                <h2>利用可能な授業一覧</h2>

                <form method="get" action="index.php" class="filter-form">
                    <label for="grade_filter">学年:</label>
                    <select name="grade_filter" id="grade_filter" onchange="this.form.submit()">
                        <option value="1" <?= $current_grade == 1 ? 'selected' : '' ?>>1年生</option>
                        <option value="2" <?= $current_grade == 2 ? 'selected' : '' ?>>2年生</option>
                        <option value="3" <?= $current_grade == 3 ? 'selected' : '' ?>>3年生</option>
                        <option value="4" <?= $current_grade == 4 ? 'selected' : '' ?>>4年生</option>
                    </select>

                    <label for="term_filter">期間:</label>
                    <select name="term_filter" id="term_filter" onchange="this.form.submit()">
                        <option value="全て" <?= $current_term == '全て' ? 'selected' : '' ?>>全て</option>
                        <option value="前期" <?= $current_term == '前期' ? 'selected' : '' ?>>前期</option>
                        <option value="後期" <?= $current_term == '後期' ? 'selected' : '' ?>>後期</option>
                        <option value="通年" <?= $current_term == '通年' ? 'selected' : '' ?>>通年</option>
                    </select>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>学年</th>
                            <th>期間</th>
                            <th>科目名</th>
                            <th>単位</th>
                            <th>選択</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($classes)): ?>
                            <tr><td colspan="5">利用可能な授業がありません。</td></tr>
                        <?php else: ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td><?php echo h($class['grade']); ?>年生</td>
                                    <td><?php echo h($class['term']); ?></td>
                                    <td><?php echo h($class['class_name']); ?></td>
                                    <td><?php echo h($class['credit']); ?></td>
                                    <td>
                                        <button class="add-button"
                                                data-class-id="<?php echo h($class['id']); ?>"
                                                data-class-name="<?php echo h($class['class_name']); ?>"
                                                data-credit="<?php echo h($class['credit']); ?>"
                                                data-day-of-week="<?php echo h($class['day_of_week']); ?>"
                                                data-time-slot="<?php echo h($class['time_slot']); ?>"
                                                data-term="<?php echo h($class['term']); ?>">選択</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="timetable-section">
                <h2>時間割</h2>

                <div id="selectedClassInfo">
                    <p>選択中の授業: <span id="currentSelectedClassName">なし</span></p>
                    <p>単位: <span id="currentSelectedClassCredit">0</span></p>
                </div>

                <form id="addTimetableForm" method="post" action="index.php" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="class_id" id="formClassId">
                    <input type="hidden" name="term" id="formTerm"> <input type="hidden" name="grade_filter" value="<?= $current_grade ?>">
                    <input type="hidden" name="term_filter" value="<?= $current_term ?>">


                    <label for="day_of_week">曜日:</label>
                    <select name="day_of_week" id="day_of_week">
                        <option value="">選択してください</option>
                        <option value="月">月</option>
                        <option value="火">火</option>
                        <option value="水">水</option>
                        <option value="木">木</option>
                        <option value="金">金</option>
                        <option value="土">土</option>
                        <option value="日">日</option>
                    </select>

                    <label for="time_slot">時限:</label>
                    <select name="time_slot" id="time_slot">
                        <option value="">選択してください</option>
                        <option value="1">1限 (9:00-10:00)</option>
                        <option value="2">2限 (10:10-11:10)</option>
                        <option value="3">3限 (11:20-12:20)</option>
                        <option value="4">4限 (13:10-14:10)</option>
                        <option value="5">5限 (14:20-15:20)</option>
                        <option value="6">6限 (15:30-16:30)</option>
                        <option value="7">7限 (16:40-17:40)</option>
                    </select>

                    <button type="submit" id="addTimetableBtn" class="disabled-button" disabled>時間割に追加</button>
                </form>

                <h3>現在の時間割 (<?= h($current_grade) ?>年生, 期間: <?= h(getTermName($current_term)) ?>)</h3>
                <table class="timetable-table">
                    <thead>
                        <tr>
                            <th>時間</th>
                            <th>月</th>
                            <th>火</th>
                            <th>水</th>
                            <th>木</th>
                            <th>金</th>
                            <th>土</th>
                            <th>日</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 7; $i++): ?>
                            <tr>
                                <td><?php echo $i; ?>限</td>
                                <?php foreach (['月', '火', '水', '木', '金', '土', '日'] as $day): ?>
                                    <td id="cell-<?php echo $day; ?>-<?php echo $i; ?>" class="time-slot">
                                        <?php
                                        $class_found = false;
                                        foreach ($user_timetable as $class_entry) {
                                            if ($class_entry['day_of_week'] == $day && $class_entry['time_slot'] == $i) {
                                                echo '<span class="class-name">' . h($class_entry['class_name']) . '</span><br>';
                                                echo '<span class="term-display-in-cell">('. h(getTermName($class_entry['term'])) . ')</span>'; // 期間表示
                                                echo '<form method="post" action="index.php" style="display:inline;">';
                                                echo '<input type="hidden" name="action" value="remove">';
                                                echo '<input type="hidden" name="user_timetable_id" value="' . h($class_entry['user_timetable_id']) . '">';
                                                echo '<input type="hidden" name="grade_filter" value="' . h($current_grade) . '">';
                                                echo '<input type="hidden" name="term_filter" value="' . h($current_term) . '">';
                                                echo '<button type="submit" class="remove-button">x</button>';
                                                echo '</form>';
                                                $class_found = true;
                                                break;
                                            }
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <p id="totalCredits">現在の期間の履修単位数: <?php echo h($total_current_credits); ?> 単位</p>

                <form id="confirmForm" method="post" action="index.php">
                    <input type="hidden" name="action" value="confirm_timetable">
                    <input type="hidden" name="grade_filter" value="<?= $current_grade ?>">
                    <input type="hidden" name="term_filter" value="<?= $current_term ?>">
                    <button type="submit" id="confirmTimetableBtn">時間割を確定する</button>
                </form>

            </div>
        </div>
    </div>

    <script>
        let selectedClassId = null;
        let selectedClassName = '';
        let selectedClassCredit = 0;
        let selectedClassTerm = ''; // 選択された授業の期間を保持する変数

        document.querySelectorAll('.add-button').forEach(button => {
            button.addEventListener('click', function() {
                selectedClassId = this.dataset.classId;
                selectedClassName = this.dataset.className;
                selectedClassCredit = this.dataset.credit;
                selectedClassTerm = this.dataset.term; // 期間情報を取得

                document.getElementById('currentSelectedClassName').textContent = selectedClassName;
                document.getElementById('currentSelectedClassCredit').textContent = selectedClassCredit;
                
                // 選択されたら「時間割に追加」ボタンを有効にする
                document.getElementById('addTimetableBtn').disabled = false;
                document.getElementById('addTimetableBtn').classList.remove('disabled-button');

                // フォームに隠しフィールドの値を設定
                document.getElementById('formClassId').value = selectedClassId;
                document.getElementById('formTerm').value = selectedClassTerm; // 期間情報をフォームに設定
            });
        });

        // 曜日と時限が選択されたときに「時間割に追加」ボタンを有効/無効にするロジック
        const dayOfWeekSelect = document.getElementById('day_of_week');
        const timeSlotSelect = document.getElementById('time_slot');
        const addTimetableBtn = document.getElementById('addTimetableBtn');

        function toggleAddButtonState() {
            if (selectedClassId && dayOfWeekSelect.value !== '' && timeSlotSelect.value !== '') {
                addTimetableBtn.disabled = false;
                addTimetableBtn.classList.remove('disabled-button');
            } else {
                addTimetableBtn.disabled = true;
                addTimetableBtn.classList.add('disabled-button');
            }
        }

        dayOfWeekSelect.addEventListener('change', toggleAddButtonState);
        timeSlotSelect.addEventListener('change', toggleAddButtonState);

        // 初期ロード時にボタンの状態を設定
        toggleAddButtonState();

    </script>
</body>
</html>