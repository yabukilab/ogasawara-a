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
            border-bottom: none; /* 2時限連続 表示を 위해 아래쪽 테두리 제거 */
        }
        .time-slot.filled-secondary {
            background-color: #e6f7ff;
            border-top: none; /* 2時限連続 表示を 위해 위쪽 테두리 제거 */
            color: #666; /* 連結されたコマは薄く表示 */
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
        /*
        // 이 부분은 이제 필요 없으므로 주석 처리하거나 삭제합니다.
        .links a { display: inline-block; margin-right: 10px; text-decoration: none; color: #007bff; }
        .links a:hover { text-decoration: underline; }
        */

        /* 確定後のスタイル */
        .timetable-finalized .add-button,
        .timetable-finalized .remove-button,
        .timetable-finalized #day_select,
        .timetable-finalized #time_select,
        .timetable-finalized button[onclick="addClassToTimetable()"],
        .timetable-finalized #confirmTimetableBtn,
        .timetable-finalized #term_filter_form button,
        .timetable-finalized #grade_filter_form button, 
        .timetable-finalized #grade_filter, /* 학년 필터 드롭다운도 비활성화 */
        .timetable-finalized #term_filter { /* 학기 필터 드롭다운도 비활성화 */
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
        .term-display-in-cell { /* 時間표 셀 안에 학기 표시용 스타일 */
            font-size: 0.7em;
            color: #666;
            margin-top: 2px;
        }
    </style>
</head>
<body>
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
            // データベース接続情報
            $host = '127.0.0.1';
            $dbName = 'mydb';
            $user = 'root';
            $password = '';

            // 学期番号を日本語に変換する関数
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
                    $timetableFilePath = __DIR__ . '/confirmed_timetable_data_grade' . $selectedGrade . '.json';
                    $currentTimetableData = [];
                    if (file_exists($timetableFilePath)) {
                        $jsonContent = file_get_contents($timetableFilePath);
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
                                if ($cellClasses === 'time-slot filled-primary') {
                                    $cellDataAttrs .= " data-linked-period='" . ($i + 1) . "'";
                                    $cellDataAttrs .= " data-is-primary='true'";
                                } elseif ($cellClasses === 'time-slot filled-secondary') {
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
        const registeredClasses = {}; 
        
        const currentSelectedGradeFromPHP = <?php echo json_encode($selectedGrade); ?>;

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
            primaryCell.dataset.isPrimary = 'true'; 

            secondaryCell.classList.add('filled-secondary');
            secondaryCell.innerHTML = `<div class="term-display-in-cell">${termMap[selectedClass.term]}</div>`; 
            secondaryCell.dataset.classId = selectedClass.id; 
            secondaryCell.dataset.className = selectedClass.name; 
            secondaryCell.dataset.classCredit = selectedClass.credit; 
            secondaryCell.dataset.classTerm = selectedClass.term; 
            secondaryCell.dataset.classGrade = selectedClass.grade; 
            secondaryCell.dataset.linkedPeriod = period; 
            secondaryCell.dataset.isPrimary = 'false'; 
            
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
            const classIdToRemove = cell.dataset.classId;
            const creditToRemove = parseInt(cell.dataset.classCredit);
            const linkedPeriod = cell.dataset.linkedPeriod ? parseInt(cell.dataset.linkedPeriod) : null;
            const classGradeToRemove = parseInt(cell.dataset.classGrade); 

            function clearCell(targetCell) {
                if (targetCell) {
                    targetCell.classList.remove('filled', 'filled-primary', 'filled-secondary'); 
                    targetCell.innerHTML = '';
                    delete targetCell.dataset.classId;
                    delete targetCell.dataset.className; 
                    delete targetCell.dataset.classCredit;
                    delete targetCell.dataset.classTerm;
                    delete targetCell.dataset.classGrade; 
                    delete targetCell.dataset.linkedPeriod; 
                    delete targetCell.dataset.isPrimary; 
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
                    
                    timetableData.push({
                        day: day,
                        period: period, 
                        classId: classId,
                        className: className,
                        classCredit: classCredit,
                        classTerm: classTerm,
                        classGrade: classGrade 
                    });
                });

                try {
                    const response = await fetch('save_timetable.php?grade=' + currentSelectedGradeFromPHP, { 
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


            const timetableCells = document.querySelectorAll('.time-slot.filled-primary, .time-slot.filled-secondary');
            
            timetableCells.forEach(cell => {
                const classTermInCell = cell.dataset.classTerm;
                
                const matchesTermFilter = (selectedFilterTerm === '0' || classTermInCell === selectedFilterTerm);

                if (matchesTermFilter) { 
                    cell.style.display = ''; 
                    if (cell.classList.contains('filled-secondary')) {
                         cell.querySelector('.term-display-in-cell').style.display = '';
                    }
                } else {
                    cell.style.display = 'none'; 
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