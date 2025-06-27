<?php
session_start();
require_once 'db_config.php'; // DB 設定及び getTermName 関数を含む

// ログイン確認 - user_idでログイン状況を確認
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;
$current_student_number = 'ゲスト'; // デフォルトはゲスト
$current_user_department = '未設定'; // デフォルトは未設定

if ($is_logged_in) {
    $current_student_number = $_SESSION['student_number']; // student_number もセッションから取得
    $current_user_department = $_SESSION['department']; // department もセッションから取得
}

// ゲストとして閲覧を許可する場合の処理
// user_idがない場合でも、GETパラメータに 'guest=true' があれば閲覧モードに入る
$is_guest_mode = !$is_logged_in && isset($_GET['guest']) && $_GET['guest'] == 'true';

// ユーザーがログインしておらず、かつゲストモードでもない場合、ログイン/登録を促すメッセージを表示
if (!$is_logged_in && !$is_guest_mode) {
    echo '<!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>授業登録 (Class Registration)</title>
        <link rel="stylesheet" href="style2.css"> </head>
    <body>
        <div class="auth-container">
            <h1>時間割登録システム</h1>
            <p>時間割を編集・保存するには、ログインまたは新規ユーザー登録が必要です。</p>
            <div class="auth-links">
                <a href="login.php">ログイン</a>
                <a href="register_user.php">新規ユーザー登録</a>
            </div>
            <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
                <a href="index.php?guest=true" style="color: #007bff; text-decoration: none;">ゲストとして時間割を見る (閲覧のみ)</a>
            </p>
        </div>
    </body>
    </html>';
    exit; // ログインしておらず、ゲストモードでもない場合、ここでスクリプト実行を中断
}

// ここからログイン済みユーザー、またはゲストモードの場合の処理

$selectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2; // 基本2年生
$selectedTermFilter = isset($_GET['term_filter']) ? $_GET['term_filter'] : '0'; // 基本全体学期

// 利用可能な授業リストの取得
$classes = [];
try {
    $sql = "SELECT id, grade, term, name, category1, category2, category3, credit FROM class WHERE grade = :grade_filter";
    $params = [':grade_filter' => $selectedGrade];

    if ($selectedTermFilter !== '0') {
        $sql .= " AND term = :term_filter";
        $params[':term_filter'] = (int)$selectedTermFilter;
    }
    $sql .= " ORDER BY name ASC"; // 授業名でソート

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $classFetchError = "授業リストの読み込みに失敗しました: " . htmlspecialchars($e->getMessage());
}

// ユーザーごとの確定済み時間割データをロード (DBからロード)
$currentTimetableData = [];
// ログイン済みユーザーの場合のみ、個人時間割をロード
if ($is_logged_in) {
    try {
        $stmt = $db->prepare("SELECT ut.day, ut.period, ut.class_id,
                                      c.name as className, c.credit as classCredit, c.term as classTerm, c.grade as classGrade
                               FROM user_timetables ut
                               JOIN class c ON ut.class_id = c.id
                               WHERE ut.user_id = :user_id AND ut.grade = :grade"); // user_id を使用
        $stmt->execute([
            ':user_id' => $current_user_id, // user_id をバインド
            ':grade' => $selectedGrade
        ]);
        $currentTimetableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to load user timetable: " . $e->getMessage());
        $currentTimetableData = [];
    }
}


// 時間帯の定義
$times = [
    1 => '9:00-10:00', 2 => '10:00-11:00', 3 => '11:00-12:00',
    4 => '13:00-14:00', 5 => '14:00-15:00', 6 => '15:00-16:00',
    7 => '16:00-17:00', 8 => '17:00-18:00', 9 => '18:00-19:00', 10 => '19:00-20:00'
];
$days_of_week = ['月', '火', '水', '木', '金', '土']; // 曜日の定義
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業登録 (Class Registration)</title>
    <link rel="stylesheet" href="style.css"> </head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo $current_user_department; ?>)
        <?php if ($is_logged_in): ?>
            <a href="logout.php">ログアウト</a>
        <?php endif; ?>
    </div>

    <h1>授業登録</h1>

    <?php if ($is_logged_in): // ログインされている場合のみボタンを表示 ?>
        <a href="confirmed_timetable.php?grade_filter=<?= htmlspecialchars($selectedGrade) ?>" class="view-confirmed-button">
            確定済み時間割を見る
        </a>
        <a href="credits_status.php" class="view-confirmed-button">
            単位取得状況を確認
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
                                <td><button class='add-button' onclick='selectClass(this)' <?= !$is_logged_in ? 'disabled class="disabled-button"' : '' ?>>選択</button></td>
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
                <select id="day_select" <?= !$is_logged_in ? 'disabled' : '' ?>>
                    <?php foreach ($days_of_week as $day_name): ?>
                        <option value="<?= htmlspecialchars($day_name) ?>"><?= htmlspecialchars($day_name) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="time_select">時限:</label>
                <select id="time_select" <?= !$is_logged_in ? 'disabled' : '' ?>>
                    <?php foreach ($times as $period => $time_range): ?>
                        <option value="<?= $period ?>"><?= $period ?>限 (<?= htmlspecialchars($time_range) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <button onclick="addClassToTimetable()" <?= !$is_logged_in ? 'disabled class="disabled-button"' : '' ?>>時間割に追加</button>
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
                    foreach ($times as $i => $time_range) { // $iは1から10までの時限
                        echo "<tr>";
                        echo "<td>" . $i . "限<br><span style='font-size:0.8em; color:#666;'>" . explode('-', $time_range)[0] . "</span></td>"; // 開始時間のみ表示

                        foreach ($days_of_week as $day_name) {
                            $cellContent = '';
                            $cellClasses = 'time-slot';
                            $cellDataAttrs = '';
                            $termDisplayInCell = '';

                            $foundClass = null;
                            foreach ($currentTimetableData as $classEntry) {
                                // PHP의 day_of_week는 '月', '火' 등 문자열이고, DB의 day는 인덱스이므로 일치시켜야 합니다.
                                // $days_of_week 배열을 사용하여 매핑
                                if ($classEntry['day'] === $day_name && (int)$classEntry['period'] === $i) {
                                    $foundClass = $classEntry;
                                    break;
                                }
                            }

                            if ($foundClass) {
                                $cellContent = htmlspecialchars($foundClass['className']) . "<br>(" . htmlspecialchars($foundClass['classCredit']) . "単位)";
                                $cellClasses .= ' filled-primary';
                                $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($foundClass['classTerm']) . "</div>";

                                $cellDataAttrs .= " data-class-id='" . htmlspecialchars($foundClass['class_id']) . "'";
                                $cellDataAttrs .= " data-class-name='" . htmlspecialchars($foundClass['className']) . "'";
                                $cellDataAttrs .= " data-class-credit='" . htmlspecialchars($foundClass['classCredit']) . "'";
                                $cellDataAttrs .= " data-class-term='" . htmlspecialchars($foundClass['classTerm']) . "'";
                                $cellDataAttrs .= " data-class-grade='" . htmlspecialchars($foundClass['classGrade']) . "'";
                                if ($is_logged_in) { // ログインしている場合のみ削除ボタンを追加
                                    $cellContent .= "<button class='remove-button' onclick='removeClassFromTimetable(this)'>X</button>";
                                }
                            }

                            // セルの出力
                            echo "<td class='{$cellClasses}' data-day='{$day_name}' data-time='{$i}' {$cellDataAttrs}>{$cellContent}{$termDisplayInCell}</td>";
                        }
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
            <div id="totalCredits">合計単位数: 0</div>
            <button id="confirmTimetableBtn" onclick="confirmTimetable()" <?= !$is_logged_in ? 'disabled class="disabled-button"' : '' ?>>この時間割で登録確定</button>
        </div>
    </div>
    <script src="script.js"></script>
    <script>
        // PHP変数をJavaScriptに渡す
        const currentSelectedGradeFromPHP = <?php echo json_encode($selectedGrade); ?>;
        const currentSelectedTermFromPHP = <?php echo json_encode($selectedTermFilter); ?>; // 学期フィルター値
        const currentLoggedInStudentNumber = <?php echo htmlspecialchars(json_encode($current_student_number)); ?>; // ゲストも含む
        const currentLoggedInUserId = <?php echo json_encode($current_user_id); ?>; // user_id を追加
        const isUserLoggedIn = <?php echo json_encode($is_logged_in); ?>; // ログイン状態を追加
        const isGuestMode = <?php echo json_encode($is_guest_mode); ?>; // ゲストモードかどうかを追加
        const initialTimetableData = <?php echo json_encode($currentTimetableData); ?>; // 初期時間割データ

        // DOMContentLoaded を使用して全ての要素がロードされた後にスクリプトを実行
        document.addEventListener('DOMContentLoaded', function() {
            initializeTimetableFromPHP(initialTimetableData); // PHPからロードしたデータで時間割を初期化
            updateFilterDisplay(); // 初期ロード時にフィルター表示を更新
            updateDisplayTotalCredits(); // 初期ロード後に合計単位数を再計算し表示

            // フィルタードロップダウン変更時に自動でフォームを送信
            document.getElementById('grade_filter').addEventListener('change', function() {
                // ゲストモードの場合、guest=true パラメータも引き継ぐ
                const guestParam = isGuestMode ? '&guest=true' : '';
                window.location.href = `index.php?grade_filter=${this.value}&term_filter=${document.getElementById('term_filter').value}${guestParam}`;
            });

            document.getElementById('term_filter').addEventListener('change', function() {
                // ゲストモードの場合、guest=true パラメータも引き継ぐ
                const guestParam = isGuestMode ? '&guest=true' : '';
                window.location.href = `index.php?grade_filter=${document.getElementById('grade_filter').value}&term_filter=${this.value}${guestParam}`;
            });

            // ログイン状態に応じてボタンを有効/無効化 (JavaScriptで制御)
            // PHP側で disabled 属性が付与されているが、JSでも再度確認・制御
            if (!isUserLoggedIn) {
                document.querySelectorAll('.add-button').forEach(button => {
                    button.disabled = true;
                    button.classList.add('disabled-button');
                });
                document.getElementById('day_select').disabled = true;
                document.getElementById('time_select').disabled = true;
                document.querySelector('button[onclick="addClassToTimetable()"]').disabled = true;
                document.querySelector('button[onclick="addClassToTimetable()"]').classList.add('disabled-button');
                document.getElementById('confirmTimetableBtn').disabled = true;
                document.getElementById('confirmTimetableBtn').classList.add('disabled-button');
            }
        });
    </script>
</body>
</html>