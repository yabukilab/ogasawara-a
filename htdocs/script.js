// 전역 변수
let selectedClass = null; // 現在選択された授業情報を保存
// 현재 시간표 데이터를 저장할 객체. 예: { '月': { '1': { class_id: '...', className: '...' } } }
let currentTimetable = {};
let totalCredits = 0; // 合計単位数

// 요일과 시차 매핑 (편의를 위해 배열로 정의)
const days = ['月', '火', '水', '木', '金', '土'];
const periods = Array.from({length: 10}, (_, i) => i + 1); // 1から10まで


// 1. ページロード時現在フィルター状態表示 (index.phpから呼び出し)
function updateFilterDisplay() {
    const gradeSelect = document.getElementById('grade_filter');
    const termSelect = document.getElementById('term_filter');
    const displayGrade = document.getElementById('displayGrade');
    const displayTerm = document.getElementById('displayTerm');

    if (gradeSelect && displayGrade) {
        displayGrade.textContent = gradeSelect.options[gradeSelect.selectedIndex].text;
    }
    if (termSelect && displayTerm) {
        displayTerm.textContent = termSelect.options[termSelect.selectedIndex].text;
    }
}


// 2. 授業リストから授業選択時情報表示
function selectClass(buttonElement) {
    const row = buttonElement.closest('tr');
    selectedClass = {
        id: row.dataset.classId,
        name: row.dataset.className,
        credit: parseInt(row.dataset.classCredit),
        term: row.dataset.classTerm,
        grade: row.dataset.classGrade // 수업 자체의 학년
    };

    document.getElementById('currentSelectedClassName').textContent = selectedClass.name;
    document.getElementById('currentSelectedClassCredit').textContent = selectedClass.credit;
}


// 3. 時間割に授業追加 (HTMLの "時間割に追加" ボタンクリック時呼び出し)
function addClassToTimetable() {
    if (!selectedClass) {
        alert('時間割に追加する授業を選択してください。');
        return;
    }

    let day;
    let targetPeriods = [];

    // ⚠️ 변경된 부분: 특정 수업 이름에 따라 요일과 시한을 고정
    if (selectedClass.name === '数学') { // 예시: '数学' 수업인 경우
        day = '月'; // 월요일
        targetPeriods = [1, 2]; // 1교시, 2교시
    } else if (selectedClass.name === '別の授業名') { // 다른 수업에 대한 규칙 추가 가능
        day = '火'; // 화요일
        targetPeriods = [3]; // 3교시
    } else {
        // 특정 규칙이 없는 수업의 경우, 기존처럼 사용자가 드롭다운에서 선택한 요일과 시한을 사용
        // (이 경우, 기존 addClassToTimetable 로직과 동일하게 1, 2교시가 기본이 됩니다)
        // 만약 사용자가 선택한 시한을 반영하고 싶다면, 'time_select' 값을 사용하도록 변경해야 합니다.
        // 현재는 day_select만 사용하고 targetPeriods는 [1,2]로 고정되어 있습니다.

        day = document.getElementById('day_select').value;
        // 현재 로직상으로 addClassToTimetable 내부에 targetPeriods = [1, 2]; 가 하드코딩 되어있습니다.
        // 만약 '特定の授業名'이 아닌 경우에도 사용자가 'time_select'에서 선택한 시한을 따르게 하려면,
        // 아래와 같이 수정해야 합니다.
        const selectedPeriod = parseInt(document.getElementById('time_select').value);
        targetPeriods = [selectedPeriod]; // 사용자가 선택한 하나의 시한
    }

    if (!day || targetPeriods.length === 0) {
        alert('授業を追加する曜日または時限が設定されていません。'); // 수업을 추가할 요일 또는 시한이 설정되지 않았습니다.
        return;
    }

    let hasAddedToAnySlot = false;
    let tempTotalCredits = totalCredits;

    for (const period of targetPeriods) {
        const cellId = `cell-${day}-${period}`;
        const targetCell = document.getElementById(cellId);

        if (!targetCell) {
            console.error(`Error: Timetable cell with ID ${cellId} not found.`);
            continue;
        }

        if (currentTimetable[day] && currentTimetable[day][period]) {
            const confirmOverwrite = confirm(`この時限 (${day}曜日 ${period}時限) にはすでに授業があります。上書きしますか？`);
            if (!confirmOverwrite) {
                continue;
            }
            tempTotalCredits -= currentTimetable[day][period].classCredit;
        }

        // 셀 내용 업데이트 (기존과 동일)
        targetCell.classList.add('filled-primary', 'filled-cell-wrapper');
        targetCell.innerHTML = `
            <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
            <div class="class-name-in-cell">${selectedClass.name}</div>
            <div class="class-detail-in-cell"></div>
            <div class="class-credit-in-cell">${selectedClass.credit}単位</div>
            <div class="term-display-in-cell">
                ${selectedClass.term == '1' ? '前期' : (selectedClass.term == '2' ? '後期' : '不明')}
            </div>
        `;

        // currentTimetable 객체 업데이트
        if (!currentTimetable[day]) {
            currentTimetable[day] = {};
        }
        currentTimetable[day][period] = {
            class_id: selectedClass.id,
            className: selectedClass.name,
            classCredit: selectedClass.credit,
            classTerm: selectedClass.term,
            classGrade: currentSelectedGradeFromPHP
        };

        tempTotalCredits += selectedClass.credit;
        hasAddedToAnySlot = true;
    }

    if (hasAddedToAnySlot) {
        totalCredits = tempTotalCredits;
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
    }

    selectedClass = null;
    document.getElementById('currentSelectedClassName').textContent = 'なし';
    document.getElementById('currentSelectedClassCredit').textContent = '0';
}
// 4. 時間割から授業削除
function removeClassFromTimetable(day, period) {
    const cellId = `cell-${day}-${period}`;
    const targetCell = document.getElementById(cellId);

    if (targetCell && currentTimetable[day] && currentTimetable[day][period]) {
        const confirmRemove = confirm('この授業を時間割から削除しますか？'); // 이 수업을 시간표에서 삭제하시겠습니까?
        if (!confirmRemove) {
            return;
        }

        // 기존 수업의 학점을 총 학점에서 제외
        totalCredits -= currentTimetable[day][period].classCredit;
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;

        // 셀 내용 초기화 및 클래스 제거
        targetCell.innerHTML = '';
        targetCell.classList.remove('filled-primary', 'filled-cell-wrapper'); // ⚠️ filled-cell-wrapper 제거

        // currentTimetable 객체에서 제거
        delete currentTimetable[day][period];
        if (Object.keys(currentTimetable[day]).length === 0) {
            delete currentTimetable[day]; // 해당 요일에 수업이 없으면 요일 객체도 제거
        }
    }
}


// 5. ページロード時PHPから渡された初期時間割データで時間割を埋める
function initializeTimetableFromPHP(data) {
    if (!data || data.length === 0) {
        console.log("No initial timetable data to load.");
        totalCredits = 0;
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
        days.forEach(day => {
            periods.forEach(period => {
                const cell = document.getElementById(`cell-${day}-${period}`);
                if (cell) {
                    cell.innerHTML = '';
                    cell.classList.remove('filled-primary', 'filled-cell-wrapper'); // ⚠️ filled-cell-wrapper 제거
                }
            });
        });
        currentTimetable = {};
        return;
    }

    totalCredits = 0;
    currentTimetable = {};

    data.forEach(item => {
        const day = item.day;
        const period = item.period;
        const cellId = `cell-${day}-${period}`;
        const targetCell = document.getElementById(cellId);

        if (targetCell) {
            targetCell.classList.add('filled-primary', 'filled-cell-wrapper'); // ⚠️ filled-cell-wrapper 추가
            targetCell.innerHTML = `
                <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
                <div class="class-name-in-cell">${item.className}</div>
                <div class="class-detail-in-cell"></div>
                <div class="class-credit-in-cell">${item.classCredit}単位</div>
                <div class="term-display-in-cell">
                    ${item.classTerm == '1' ? '前期' : (item.classTerm == '2' ? '後期' : '不明')}
                </div>
            `;
            totalCredits += item.classCredit;

            if (!currentTimetable[day]) {
                currentTimetable[day] = {};
            }
            currentTimetable[day][period] = {
                class_id: item.class_id,
                className: item.className,
                classCredit: item.classCredit,
                classTerm: item.classTerm,
                classGrade: item.classGrade
            };
        } else {
            console.warn(`Cell not found for day ${day}, period ${period}.`);
        }
    });

    document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
}


// 6. 時間割確定およびサーバー保存
function confirmTimetable() {
    if (!isUserLoggedIn) {
        alert('時間割を保存するにはログインが必要です。'); // 시간표를 저장하려면 로그인해야 합니다.
        window.location.href = 'login.php'; // ログインページへリダイレクト
        return;
    }

    const timetableToSave = [];
    for (const day of days) {
        if (currentTimetable[day]) {
            for (const period of periods) {
                if (currentTimetable[day][period]) {
                    timetableToSave.push({
                        day: day,
                        period: period,
                        class_id: currentTimetable[day][period].class_id,
                        // ★ 중요: 현재 필터링된 학년으로 시간표를 저장.
                        // 이는 사용자가 보고 있는 "현재 학년"의 시간표를 저장한다는 의미입니다.
                        grade: currentSelectedGradeFromPHP
                    });
                }
            }
        }
    }

    if (timetableToSave.length === 0) {
        alert('保存する授業がありません。時間割に授業を追加してください。'); // 저장할 수업이 없습니다. 시간표에 수업을 추가해주세요.
        return;
    }

    if (!confirm(`現在の時間割 (${totalCredits}単位) を保存しますか？`)) { // 현재 시간표를 저장하시겠습니까?
        return;
    }

    fetch('save_timetable.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            userId: currentLoggedInUserId,
            // save_timetable.php에서도 이 'grade' 값을 사용하여 해당 학년의 시간표를 저장합니다.
            grade: currentSelectedGradeFromPHP,
            timetableData: timetableToSave
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('時間割が正常に保存されました！'); // 시간표가 성공적으로 저장되었습니다!
            // 保存後必要であればページをリロードまたはUIを更新
            // window.location.reload();
        } else {
            alert('時間割の保存に失敗しました: ' + data.message); // 시간표 저장에 실패했습니다.
            console.error('Save failed:', data.message);
        }
    })
    .catch(error => {
        alert('時間割の保存中にエラーが発生しました。'); // 시간표 저장 중 오류가 발생했습니다.
        console.error('Error saving timetable:', error);
    });
}