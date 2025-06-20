<?php
session_start(); // セッション開始

// ログイン確認 - student_number로 로그인 여부 확인
$is_logged_in = isset($_SESSION['student_number']);
$current_student_number = $is_logged_in ? $_SESSION['student_number'] : 'ゲスト';

// ユーザーがログインしていない場合、時間割機能の代わりにログイン/登録を促すメッセージを表示
if (!$is_logged_in) {
    echo '<!DOCTYPE html>
    <html lang="ja">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>授業登録 (Class Registration)</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; text-align: center; }
            .auth-container { max-width: 600px; margin: 100px auto; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
            h1 { color: #333; margin-bottom: 20px; }
            p { color: #555; font-size: 1.1em; margin-bottom: 25px; }
            .auth-links a {
                display: inline-block;
                padding: 12px 25px;
                margin: 0 10px;
                background-color: #007bff;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                font-size: 1.1em;
                transition: background-color 0.3s ease;
            }
            .auth-links a:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="auth-container">
            <h1>時間割登録システム</h1>
            <p>時間割を編集・保存するには、ログインまたは新規ユーザー登録が必要です。</p>
            <div class="auth-links">
                <a href="login.php">ログイン</a>
                <a href="register_user.php">新規ユーザー登録</a>
            </div>
        </div>
    </body>
    </html>';
    exit; // ログインしていない場合、ここでスクリプト実行を中断
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業登録 (Class Registration)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { color: #333; }
        .container { display: flex; gap: 20px; }
        .class-list-section, .timetable-section { flex: 1; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .add-button { background-color: #4CAF50; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .add-button:hover { background-color: #45a049; }
        /* 時限セルの高さ調整 */
        .time-slot { 
            width: 100%; 
            min-height: 50px; 
            border: 1px solid #eee; 
            text-align: center; 
            vertical-align: middle; 
            position: relative; 
            padding: 5px; 
            font-size: 0.9em;
            box-sizing: border-box;
            overflow: hidden; /* 内容が 넘칠 경우 숨김 */
        }
        /* 2時限連続表示に対応するためのクラス */
        .time-slot.filled-primary {
            background-color: #e6f7ff;
            border-bottom: none; 
        }
        .time-slot.filled-secondary {
            background-color: #e6f7ff;
            border-top: none; 
            color: #666; 
            font-size: 0.8em;
            text-align: center;
        }

        .timetable-table th { width: 10%; }
        .timetable-table td { width: 15%; }
        .class-item { margin-bottom: 10px; padding: 8px; border: 1px solid #eee; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
        .class-item span { flex-grow: 1; }
        select, button { padding: 8px; margin-right: 5px; }
        #selectedClassInfo { margin-top: 15px; padding: 10px; background-color: #f9f9f9; border: 1px dashed #ccc; border-radius: 5px; }
        .remove-button { background-color: #f44336; color: white; padding: 3px 6px; border: none; border-radius: 3px; cursor: pointer; font-size: 0.7em; position: absolute; top: 2px; right: 2px;}

        /* 確定後のスタイル */
        .timetable-finalized .add-button,
        .timetable-finalized .remove-button,
        .timetable-finalized #day_select,
        .timetable-finalized #time_select,
        .timetable-finalized button[onclick="addClassToTimetable()"],
        .timetable-finalized #confirmTimetableBtn,
        .timetable-finalized #term_filter_form button,
        .timetable-finalized #grade_filter_form button, 
        .timetable-finalized #grade_filter, 
        .timetable-finalized #term_filter { 
            pointer-events: none;
            opacity: 0.5;
            cursor: not-allowed;
        }
        #confirmTimetableBtn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
        #confirmTimetableBtn:hover:not(:disabled) {
            background-color: #0056b3;
        }
        #totalCredits {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 15px;
            padding: 10px;
            background-color: #e0ffe0;
            border: 1px solid #a0d0a0;
            border-radius: 5px;
        }
        .term-display-in-cell { 
            font-size: 0.7em;
            color: #666;
            margin-top: 2px;
        }
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: #555;
        }
        .user-info a {
            color: #007bff;
            text-decoration: none;
            margin-left: 10px;
        }
        .user-info a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?>
        <?php if ($is_logged_in): ?>
            <a href="logout.php">ログアウト</a>
        <?php endif; ?>
    </div>

    <h1>授業登録</h1>

    <div class="container">
        <div class="class-list-section">
            <h2>利用可能な授業一覧</h2>
            
            <form action="" method="get" id="grade_filter_form">
                <label for="grade_filter">学年フィルタ:</label>
                <select name="grade_filter" id="grade_filter">
                    <?php
                    $selectedGrade = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2; 
                    for ($g = 1; $g <= 4; $g++) {
                        echo "<option value='{$g}'" . ($selectedGrade === $g ? ' selected' : '') . ">{$g}年生</option>";
                    }
                    ?>
                </select>
                <input type="hidden" name="term_filter" value="<?php echo htmlspecialchars(isset($_GET['term_filter']) ? $_GET['term_filter'] : '0'); ?>">
                <button type="submit">学年フィルタ適用</button>
            </form>

            <form action="" method="get" id="term_filter_form"> 
                <label for="term_filter">学期フィルタ:</label>
                <select name="term_filter" id="term_filter">
                    <option value="0" <?php echo (isset($_GET['term_filter']) && $_GET['term_filter'] == '0') ? 'selected' : ''; ?>>全て (前期/後期)</option>
                    <option value="1" <?php echo (isset($_GET['term_filter']) && $_GET['term_filter'] == '1') ? 'selected' : ''; ?>>1 (前期)</option>
                    <option value="2" <?php echo (isset($_GET['term_filter']) && $_GET['term_filter'] == '2') ? 'selected' : ''; ?>>2 (後期)</option>
                </select>
                <input type="hidden" name="grade_filter" value="<?php echo htmlspecialchars($selectedGrade); ?>">
                <button type="submit">学期フィルタ適用</button>
            </form>
            
            <?php
            $host = '127.0.0.1';
            $dbName = 'mydb';
            $user = 'root';
            $password = '';

            function getTermName($term_num) {
                switch ($term_num) {
                    case 1: return '前期';
                    case 2: return '後期';
                    default: return '不明';
                }
            }

            try {
                $pdo = new PDO("mysql:host=$host;dbname=$dbName;charset=utf8", $user, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $sql = "SELECT id, grade, term, name, category1, category2, category3, credit FROM class WHERE 1=1"; 

                $params = [];

                $sql .= " AND grade = :grade_filter";
                $params[':grade_filter'] = $selectedGrade;
                
                if (isset($_GET['term_filter']) && $_GET['term_filter'] !== '0') {
                    $sql .= " AND term = :term_filter";
                    $params[':term_filter'] = (int)$_GET['term_filter'];
                }
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params); 
                $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($classes)) {
                    echo "<p>利用可能な授業がありません。</p>"; 
                } else {
                    echo "<table>";
                    echo "<thead><tr><th>学年</th><th>学期</th><th>授業名</th><th>単位</th><th>アクション</th></tr></thead>"; 
                    echo "<tbody>";
                    foreach ($classes as $class) {
                        echo "<tr data-class-id='" . htmlspecialchars($class['id']) . "' 
                                  data-class-name='" . htmlspecialchars($class['name']) . "' 
                                  data-class-credit='" . htmlspecialchars($class['credit']) . "' 
                                  data-class-term='" . htmlspecialchars($class['term']) . "' 
                                  data-class-grade='" . htmlspecialchars($class['grade']) . "'>"; 
                        echo "<td>" . htmlspecialchars($class['grade']) . "年生</td>"; 
                        echo "<td>" . getTermName($class['term']) . "</td>";
                        echo "<td>" . htmlspecialchars($class['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($class['credit']) . "</td>";
                        echo "<td><button class='add-button' onclick='selectClass(this)'>選択</button></td>";
                        echo "</tr>";
                    }
                    echo "</tbody>";
                    echo "</table>";
                }

            } catch (PDOException | Exception $e) { 
                echo "<p style='color: red;'>エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
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
                    <option value="月">月</option>
                    <option value="火">火</option>
                    <option value="水">水</option>
                    <option value="木">木</option>
                    <option value="金">金</option>
                    <option value="土">土</option>
                </select>
                <label for="time_select">時限:</label>
                <select id="time_select">
                    <?php
                    $times = [
                        1 => '9:00-10:00', 2 => '10:00-11:00', 3 => '11:00-12:00',
                        4 => '13:00-14:00', 5 => '14:00-15:00', 6 => '15:00-16:00',
                        7 => '16:00-17:00', 8 => '17:00-18:00', 9 => '18:00-19:00', 10 => '19:00-20:00'
                    ];
                    foreach ($times as $period => $time_range) {
                        echo "<option value='{$period}'>{$period}限 ({$time_range})</option>";
                    }
                    ?>
                </select>
                <button onclick="addClassToTimetable()">時間割に追加</button>
            </div>

            <h3 id="currentTimetableInfo">時間割 (現在の学年: <?php echo htmlspecialchars($selectedGrade); ?>年生, 学期: 全て)</h3>
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
                    <?php
                    // 현재 로그인된 student_number를 사용하여 파일 경로에 추가
                    // $_SESSION['student_id'] 대신 $_SESSION['student_number'] 사용
                    $userSpecificFilePath = __DIR__ . '/confirmed_timetable_data_student' . $_SESSION['student_number'] . '_grade' . $selectedGrade . '.json';
                    $currentTimetableData = [];
                    if (file_exists($userSpecificFilePath)) {
                        $jsonContent = file_get_contents($userSpecificFilePath);
                        if ($jsonContent !== false) {
                            $decodedData = json_decode($jsonContent, true);
                            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedData)) {
                                $currentTimetableData = $decodedData;
                            }
                        }
                    }

                    for ($i = 1; $i <= 10; $i++) {
                        echo "<tr>";
                        echo "<td>" . $i . "限<br>";
                        
                        $start_time = '';
                        if ($i == 1) $start_time = '9:00'; else if ($i == 2) $start_time = '10:00';
                        else if ($i == 3) $start_time = '11:00'; else if ($i == 4) $start_time = '13:00';
                        else if ($i == 5) $start_time = '14:00'; else if ($i == 6) $start_time = '15:00';
                        else if ($i == 7) $start_time = '16:00'; else if ($i == 8) $start_time = '17:00';
                        else if ($i == 9) $start_time = '18:00'; else if ($i == 10) $start_time = '19:00';
                        
                        echo "<span style='font-size:0.8em; color:#666;'>{$start_time}</span>";
                        echo "</td>";
                        
                        $days_of_week = ['月', '火', '水', '木', '金', '土'];
                        foreach ($days_of_week as $day_name) {
                            $cellContent = '';
                            $cellClasses = 'time-slot';
                            $cellDataAttrs = ''; 
                            $termDisplayInCell = '';

                            $foundClass = null;
                            foreach ($currentTimetableData as $classEntry) {
                                if ($classEntry['day'] === $day_name && intval($classEntry['period']) === $i) {
                                    $foundClass = $classEntry;
                                    break;
                                }
                            }

                            if ($foundClass === null && $i > 1) { 
                                foreach ($currentTimetableData as $classEntry) {
                                    if ($classEntry['day'] === $day_name && intval($classEntry['period']) === ($i - 1)) {
                                        $foundClass = $classEntry; 
                                        $cellContent = "連続コマ";
                                        $cellClasses .= ' filled-secondary';
                                        $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($foundClass['classTerm']) . "</div>";
                                        break;
                                    }
                                }
                            }
                            
                            if ($foundClass) {
                                if ($cellContent !== "連続コマ") { 
                                    $cellContent = htmlspecialchars($foundClass['className']) . "<br>(" . htmlspecialchars($foundClass['classCredit']) . "単位)";
                                    $cellClasses .= ' filled-primary';
                                    $termDisplayInCell = "<div class='term-display-in-cell'>" . getTermName($foundClass['classTerm']) . "</div>";
                                }
                                
                                $cellDataAttrs .= " data-class-id='" . htmlspecialchars($foundClass['classId']) . "'";
                                $cellDataAttrs .= " data-class-name='" . htmlspecialchars($foundClass['className']) . "'";
                                $cellDataAttrs .= " data-class-credit='" . htmlspecialchars($foundClass['classCredit']) . "'";
                                $cellDataAttrs .= " data-class-term='" . htmlspecialchars($foundClass['classTerm']) . "'";
                                $cellDataAttrs .= " data-class-grade='" . htmlspecialchars($foundClass['classGrade']) . "'";
                                if (isset($foundClass['isPrimary']) && $foundClass['isPrimary'] === 'true') { // isPrimary 속성 추가 확인
                                    $cellDataAttrs .= " data-linked-period='" . ($i + 1) . "'";
                                    $cellDataAttrs .= " data-is-primary='true'";
                                } elseif (isset($foundClass['isPrimary']) && $foundClass['isPrimary'] === 'false') {
                                    $cellDataAttrs .= " data-linked-period='" . ($i - 1) . "'";
                                    $cellDataAttrs .= " data-is-primary='false'";
                                }


                                $cellContent .= "<button class='remove-button' onclick='removeClassFromTimetable(this)'>X</button>";
                            }

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

    <script>
        let selectedClass = null; 
        let totalCredits = 0; 
        let isTimetableFinalized = false; 
        
        const currentSelectedGradeFromPHP = <?php echo json_encode($selectedGrade); ?>;
        // 로그인된 사용자 학번을 JavaScript에서 사용 (PHP에서 세션으로 넘어옴)
        const currentLoggedInStudentNumber = <?php echo json_encode($_SESSION['student_number'] ?? ''); ?>; // student_number로 변경

        const termMap = {
            '0': '全て', 
            '1': '前期', 
            '2': '後期'  
        };
        
        function updateDisplayTotalCredits() {
            let currentTotal = 0;
            const uniqueClassesInTimetable = new Set(); 

            document.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                const classId = cell.dataset.classId;
                const classCredit = parseInt(cell.dataset.classCredit);
                
                if (classId && !uniqueClassesInTimetable.has(classId)) {
                    currentTotal += classCredit;
                    uniqueClassesInTimetable.add(classId);
                }
            });
            totalCredits = currentTotal; 
            document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
        }

        function selectClass(button) {
            if (isTimetableFinalized) return;

            const row = button.closest('tr');
            selectedClass = {
                id: row.dataset.classId,
                name: row.dataset.className,
                credit: parseInt(row.dataset.classCredit),
                term: row.dataset.classTerm, 
                grade: parseInt(row.dataset.classGrade) 
            };
            document.getElementById('currentSelectedClassName').textContent = selectedClass.name;
            document.getElementById('currentSelectedClassCredit').textContent = selectedClass.credit;
            
            const currentTermText = termMap[selectedClass.term] || '不明';
            document.getElementById('currentTimetableInfo').textContent = `時間割 (選択中の学年: ${selectedClass.grade}年生, 学期: ${currentTermText})`;

            document.querySelectorAll('.add-button').forEach(btn => {
                btn.style.backgroundColor = '#4CAF50';
            });
            button.style.backgroundColor = '#2196F3';
        }

        function addClassToTimetable() {
            if (isTimetableFinalized) return;
            if (!selectedClass) {
                alert('時間割に追加する授業を選択してください。'); 
                return;
            }

            const day = document.getElementById('day_select').value;
            const period = parseInt(document.getElementById('time_select').value); 
            const nextPeriod = period + 1; 

            if (period === 10) {
                alert('10時限を選択した場合、2時限連続の授業は登録できません。'); 
                return;
            }

            const primaryCell = document.querySelector(`.time-slot[data-day="${day}"][data-time="${period}"]`);
            const secondaryCell = document.querySelector(`.time-slot[data-day="${day}"][data-time="${nextPeriod}"]`);

            if (!primaryCell || !secondaryCell || primaryCell.dataset.classId || secondaryCell.dataset.classId) {
                alert('選択した時間帯または連続する時限には既に授業が追加されています。'); 
                return;
            }

            primaryCell.classList.add('filled-primary');
            primaryCell.innerHTML = `
                ${selectedClass.name}<br>
                (${selectedClass.credit}単位)
                <div class="term-display-in-cell">${termMap[selectedClass.term]}</div> 
                <button class="remove-button" onclick="removeClassFromTimetable(this)">X</button>
            `;
            primaryCell.dataset.classId = selectedClass.id;
            primaryCell.dataset.className = selectedClass.name; 
            primaryCell.dataset.classCredit = selectedClass.credit;
            primaryCell.dataset.classTerm = selectedClass.term;
            primaryCell.dataset.classGrade = selectedClass.grade; 
            primaryCell.dataset.linkedPeriod = nextPeriod; 
            primaryCell.dataset.isPrimary = 'true'; // 이 수업이 2연속 시간표의 시작임을 표시

            secondaryCell.classList.add('filled-secondary');
            secondaryCell.innerHTML = `<div class="term-display-in-cell">${termMap[selectedClass.term]}</div>`; 
            secondaryCell.dataset.classId = selectedClass.id; 
            secondaryCell.dataset.className = selectedClass.name; 
            secondaryCell.dataset.classCredit = selectedClass.credit; 
            secondaryCell.dataset.classTerm = selectedClass.term; 
            secondaryCell.dataset.classGrade = selectedClass.grade; 
            secondaryCell.dataset.linkedPeriod = period; 
            secondaryCell.dataset.isPrimary = 'false'; // 이 수업이 2연속 시간표의 끝임을 표시
            
            updateDisplayTotalCredits();

            selectedClass = null;
            document.getElementById('currentSelectedClassName').textContent = 'なし'; 
            document.getElementById('currentSelectedClassCredit').textContent = '0';
            document.querySelectorAll('.add-button').forEach(btn => {
                btn.style.backgroundColor = '#4CAF50';
            });
            
            applyTimetableFilter();
        }

        function removeClassFromTimetable(button) {
            if (isTimetableFinalized) return;

            const cell = button.closest('.time-slot'); 
            const day = cell.dataset.day;
            const period = parseInt(cell.dataset.time); 
            const linkedPeriod = cell.dataset.linkedPeriod ? parseInt(cell.dataset.linkedPeriod) : null;

            function clearCell(targetCell) {
                if (targetCell) {
                    targetCell.classList.remove('filled', 'filled-primary', 'filled-secondary'); 
                    targetCell.innerHTML = '';
                    // data- 속성 모두 제거
                    for (const key in targetCell.dataset) {
                        delete targetCell.dataset[key];
                    }
                    targetCell.style.display = ''; // 숨겨져 있던 셀을 다시 보이게 함 (필터링 때문에)
                }
            }

            if (linkedPeriod) {
                const primaryCellPeriod = Math.min(period, linkedPeriod); 
                const secondaryCellPeriod = Math.max(period, linkedPeriod); 
                
                const primaryCellToClear = document.querySelector(`.time-slot[data-day="${day}"][data-time="${primaryCellPeriod}"]`);
                const secondaryCellToClear = document.querySelector(`.time-slot[data-day="${day}"][data-time="${secondaryCellPeriod}"]`);

                clearCell(primaryCellToClear);
                clearCell(secondaryCellToClear);

            } else {
                console.warn("Linked period not found for cell, clearing single cell.", cell);
                clearCell(cell);
            }

            updateDisplayTotalCredits();
            
            applyTimetableFilter();
        }

        async function confirmTimetable() { 
            if (isTimetableFinalized) {
                alert('時間割は既に確定されています。'); 
                return;
            }

            if (confirm('この時間割で確定しますか？確定すると、変更はできなくなります。')) { 
                isTimetableFinalized = true;
                document.getElementById('timetableSection').classList.add('timetable-finalized');
                
                const timetableData = [];
                document.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                    const day = cell.dataset.day;
                    const period = parseInt(cell.dataset.time);
                    const classId = cell.dataset.classId;
                    const className = cell.dataset.className;
                    const classCredit = parseInt(cell.dataset.classCredit);
                    const classTerm = cell.dataset.classTerm;
                    const classGrade = parseInt(cell.dataset.classGrade); 
                    const isPrimary = cell.dataset.isPrimary; // isPrimary 속성 추가 저장

                    timetableData.push({
                        day: day,
                        period: period, 
                        classId: classId,
                        className: className,
                        classCredit: classCredit,
                        classTerm: classTerm,
                        classGrade: classGrade,
                        isPrimary: isPrimary // isPrimary 속성 저장
                    });
                });

                try {
                    // save_timetable.php로 student_number와 grade를 함께 전송
                    const response = await fetch('save_timetable.php?student_number=' + currentLoggedInStudentNumber + '&grade=' + currentSelectedGradeFromPHP, { 
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(timetableData)
                    });

                    const result = await response.json();

                    if (result.success) {
                        alert('時間割が確定され、登録されました！'); 
                        window.location.href = 'confirmed_timetable.php'; 
                    } else {
                        alert('時間割の保存に失敗しました: ' + result.message); 
                        isTimetableFinalized = false; 
                        document.getElementById('timetableSection').classList.remove('timetable-finalized');
                    }
                } catch (error) {
                    alert('サーバーとの通信中にエラーが発生しました: ' + error.message); 
                    isTimetableFinalized = false; 
                    document.getElementById('timetableSection').classList.remove('timetable-finalized');
                }
            }
        }

        function applyTimetableFilter() {
            const selectedFilterTerm = document.getElementById('term_filter').value;
            const selectedFilterGrade = currentSelectedGradeFromPHP.toString(); 

            const currentTermText = termMap[selectedFilterTerm] || '全て';
            const currentGradeText = selectedFilterGrade + '年生';
            document.getElementById('currentTimetableInfo').textContent = `時間割 (現在の学年: ${currentGradeText}, 学期: ${currentTermText})`;


            const timetableCells = document.querySelectorAll('.time-slot'); // 모든 시간표 셀을 선택
            
            timetableCells.forEach(cell => {
                // 셀 초기화
                cell.style.display = ''; // 일단 모든 셀을 보이게 한다

                const classTermInCell = cell.dataset.classTerm;
                
                // 해당 셀이 유효한 시간표 데이터를 가지고 있는지 확인 (filled-primary 또는 filled-secondary)
                const hasClassData = cell.dataset.classId !== undefined;

                // 필터 조건에 맞지 않으면 숨김
                if (hasClassData && selectedFilterTerm !== '0' && classTermInCell !== selectedFilterTerm) {
                    cell.style.display = 'none'; 
                }
                // 연속 수업의 보조 셀인 경우, 메인 셀이 필터링되어 숨겨지면 함께 숨김
                if (cell.classList.contains('filled-secondary')) {
                    const primaryPeriod = parseInt(cell.dataset.linkedPeriod);
                    const day = cell.dataset.day;
                    const primaryCell = document.querySelector(`.time-slot[data-day="${day}"][data-time="${primaryPeriod}"]`);
                    if (primaryCell && primaryCell.style.display === 'none') {
                        cell.style.display = 'none';
                    }
                }
            });
        }

        window.onload = function() {
            updateDisplayTotalCredits();
            applyTimetableFilter(); 
        };

        document.getElementById('term_filter').addEventListener('change', function() {
            document.getElementById('term_filter_form').submit(); 
        });

        document.getElementById('grade_filter').addEventListener('change', function() {
            document.getElementById('grade_filter_form').submit(); 
        });
    </script>
</body>
</html>
