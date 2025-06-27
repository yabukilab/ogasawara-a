<?php
// ... (기존 PHP 코드 유지) ...

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
                    <input type="hidden" name="term" id="formTerm">
                    <input type="hidden" name="grade_filter" value="<?= $current_grade ?>">
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
                                                echo '<span class="term-display-in-cell">('. h(getTermName($class_entry['term'])) . ')</span>';
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
        // ... (기존 JavaScript 코드 유지) ...
    </script>
</body>
</html>