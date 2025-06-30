<?php
session_start();
require_once 'db_config.php'; // DB 設定と h()、getTermName() 関数を含む

// Debugging: Display all errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    $message = "授業情報の読み込みに失敗しました: " . $e->getMessage(); // エラーメッセージに詳細を追加
    $message_type = 'error';
}

// ユーザーの時間割データ取得 (user_timetables テーブルを使用しないため、常に空)
// ここでは時間割表示をダミーデータで表示するか、空にするかを選択できます。
// 現在は空にするため、関連ロジックは削除またはコメントアウトします。
$user_timetable = []; // user_timetables を使用しないため空にする
$total_current_credits = 0; // 単位計算も無効化


// 時間割への追加/削除/確定処理 (すべて無効化または削除)
// POST リクエストの処理は、user_timetables に依存しないように変更する必要があります。
// ここでは一旦、機能全体をコメントアウトまたは削除します。
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $message = "時間割関連の機能は現在無効化されています。";
    $message_type = 'error';
    // リダイレクトなしでメッセージを表示するために、ここでは exit; しない
    // header("Location: index.php?grade_filter={$current_grade}&term_filter={$current_term}&message=" . urlencode($message) . "&message_type=" . $message_type);
    // exit;
}


// URLパラメータからのメッセージ表示
if (isset($_GET['message']) && isset($_GET['message_type'])) {
    $message = h($_GET['message']);
    $message_type = h($_GET['message_type']);
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
                                        <button class="add-button disabled-button" disabled>選択 (現在無効)</button>
                                        </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="timetable-section">
                <h2>時間割 (現在無効)</h2>

                <div id="selectedClassInfo">
                    <p>選択中の授業: <span id="currentSelectedClassName">なし</span></p>
                    <p>単位: <span id="currentSelectedClassCredit">0</span></p>
                </div>

                <form id="addTimetableForm" method="post" action="index.php" style="margin-bottom: 20px;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="class_id" id="formClassId">
                    <input type="hidden" name="term" id="formTerm">
                    <input type="hidden" name="grade_filter" value="<?= $current_grade ?>">
                    <input type="hidden" name="term_filter" value="<?= $current_term ?>">


                    <label for="day_of_week">曜日:</label>
                    <select name="day_of_week" id="day_of_week" disabled>
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
                    <select name="time_slot" id="time_slot" disabled>
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
                                        </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
                <p id="totalCredits">現在の期間の履修単位数: 0 単位 (現在無効)</p>

                <form id="confirmForm" method="post" action="index.php">
                    <input type="hidden" name="action" value="confirm_timetable">
                    <input type="hidden" name="grade_filter" value="<?= $current_grade ?>">
                    <input type="hidden" name="term_filter" value="<?= $current_term ?>">
                    <button type="submit" id="confirmTimetableBtn" class="disabled-button" disabled>時間割を確定する</button>
                </form>

            </div>
        </div>
    </div>

    <script>
        // 時間割選択・追加のJavaScriptも無効化
        // let selectedClassId = null;
        // let selectedClassName = '';
        // let selectedClassCredit = 0;
        // let selectedClassTerm = '';

        document.querySelectorAll('.add-button').forEach(button => {
            // button.addEventListener('click', function() {
            //     selectedClassId = this.dataset.classId;
            //     selectedClassName = this.dataset.className;
            //     selectedClassCredit = this.dataset.credit;
            //     selectedClassTerm = this.dataset.term;

            //     document.getElementById('currentSelectedClassName').textContent = selectedClassName;
            //     document.getElementById('currentSelectedClassCredit').textContent = selectedClassCredit;
                
            //     document.getElementById('addTimetableBtn').disabled = false;
            //     document.getElementById('addTimetableBtn').classList.remove('disabled-button');

            //     document.getElementById('formClassId').value = selectedClassId;
            //     document.getElementById('formTerm').value = selectedClassTerm;
            // });
        });

        // const dayOfWeekSelect = document.getElementById('day_of_week');
        // const timeSlotSelect = document.getElementById('time_slot');
        // const addTimetableBtn = document.getElementById('addTimetableBtn');

        // function toggleAddButtonState() {
        //     if (selectedClassId && dayOfWeekSelect.value !== '' && timeSlotSelect.value !== '') {
        //         addTimetableBtn.disabled = false;
        //         addTimetableBtn.classList.remove('disabled-button');
        //     } else {
        //         addTimetableBtn.disabled = true;
        //         addTimetableBtn.classList.add('disabled-button');
        //     }
        // }

        // dayOfWeekSelect.addEventListener('change', toggleAddButtonState);
        // timeSlotSelect.addEventListener('change', toggleAddButtonState);

        // toggleAddButtonState();
    </script>
</body>
</html>