// script.js

// ... (기존 전역 변수 및 다른 함수들은 그대로 유지) ...

// 전역 변수: 현재 시간표에 추가된 각 수업의 학점을 추적하기 위한 Map
// key: class_id, value: credit
let addedClassCredits = new Map();


// 3. 時間割に授業追加 (HTMLの "時間割に追加" ボタンクリック時呼び出し)
function addClassToTimetable() {
    if (!selectedClass) {
        alert('時間割に追加する授業を選択してください。'); // 시간표에 추가할 수업을 선택해주세요.
        return;
    }

    const day = document.getElementById('day_select').value;
    const startPeriod = parseInt(document.getElementById('time_select').value); // 사용자가 선택한 시작 시한

    if (!day || isNaN(startPeriod)) {
        alert('曜日と時限を選択してください。'); // 요일과 시한을 선택해주세요.
        return;
    }

    // 수업은 항상 2시간 진행되므로, 시작 시한과 다음 시한을 targetPeriods에 추가
    const targetPeriods = [startPeriod, startPeriod + 1];

    // 시간표 범위(1~10교시)를 벗어나는지 확인
    if (targetPeriods[1] > 10) {
        alert(`${day}曜日 ${startPeriod}時限から2時間授業を登録することはできません。時限の範囲を超えています。`);
        return;
    }

    let allSlotsAvailable = true; // 모든 칸이 비어있는지 또는 덮어쓰기 허용되는지
    let conflicts = []; // 충돌하는 칸 정보를 저장

    // 첫 번째 루프: 모든 대상 칸에 대한 충돌 여부 사전 확인
    for (const period of targetPeriods) {
        if (currentTimetable[day] && currentTimetable[day][period]) {
            conflicts.push(`${day}曜日 ${period}時限`);
            allSlotsAvailable = false;
        }
    }

    if (!allSlotsAvailable) {
        // 충돌이 발생했을 경우 사용자에게 확인
        const confirmOverwrite = confirm(`選択した時限 (${conflicts.join(', ')}) にはすでに授業があります。上書きしますか？`);
        if (!confirmOverwrite) {
            return; // 사용자가 취소하면 함수 종료
        }
    }

    // 학점 계산을 위한 임시 변수
    let creditsToAdd = selectedClass.credit;

    // 덮어쓰기 로직에 따라 기존 학점 계산 반영
    // 이 수업의 기존 학점은 이미 추가되었을 수 있으므로 먼저 제거
    if (addedClassCredits.has(selectedClass.id)) {
        totalCredits -= addedClassCredits.get(selectedClass.id);
        addedClassCredits.delete(selectedClass.id);
    }

    let hasAddedToAnySlot = false;

    // 두 번째 루프: 실제 시간표 셀 업데이트
    for (const period of targetPeriods) {
        const cellId = `cell-${day}-${period}`;
        const targetCell = document.getElementById(cellId);

        if (!targetCell) {
            console.error(`Error: Timetable cell with ID ${cellId} not found.`);
            continue; // 다음 교시로 넘어감
        }

        // 셀 내용 업데이트
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
            classCredit: selectedClass.credit, // 이 셀에 표시되는 학점은 그대로 유지
            classTerm: selectedClass.term,
            classGrade: currentSelectedGradeFromPHP
        };
        hasAddedToAnySlot = true;
    }

    // 모든 대상 칸에 대해 루프를 돈 후, 수업이 하나라도 성공적으로 추가되었다면 총 학점 업데이트
    if (hasAddedToAnySlot) {
        // 이 수업의 학점을 addedClassCredits 맵에 추가 (중복 방지)
        addedClassCredits.set(selectedClass.id, creditsToAdd);
        totalCredits += creditsToAdd; // 한 번만 합산

        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
    }

    // 선택된 수업 정보 초기화
    selectedClass = null;
    document.getElementById('currentSelectedClassName').textContent = 'なし';
    document.getElementById('currentSelectedClassCredit').textContent = '0';
}

// 4. 時間割から授業削除
function removeClassFromTimetable(day, period) {
    const cellId = `cell-${day}-${period}`;
    const targetCell = document.getElementById(cellId);

    if (targetCell && currentTimetable[day] && currentTimetable[day][period]) {
        const confirmRemove = confirm('この授業を時間割から削除しますか？');
        if (!confirmRemove) {
            return;
        }

        const removedClassId = currentTimetable[day][period].class_id;

        // 셀 내용 초기화 및 클래스 제거
        targetCell.innerHTML = '';
        targetCell.classList.remove('filled-primary', 'filled-cell-wrapper');

        // currentTimetable 객체에서 제거
        delete currentTimetable[day][period];
        if (Object.keys(currentTimetable[day]).length === 0) {
            delete currentTimetable[day];
        }

        // 해당 수업이 시간표에서 완전히 제거되었는지 확인하고 학점 감소
        let isClassStillInTimetable = false;
        for (const d of days) {
            if (currentTimetable[d]) {
                for (const p of periods) {
                    if (currentTimetable[d][p] && currentTimetable[d][p].class_id === removedClassId) {
                        isClassStillInTimetable = true;
                        break;
                    }
                }
            }
            if (isClassStillInTimetable) break;
        }

        if (!isClassStillInTimetable) {
            // 해당 수업이 시간표에서 완전히 사라졌을 때만 학점 감소
            if (addedClassCredits.has(removedClassId)) {
                totalCredits -= addedClassCredits.get(removedClassId);
                addedClassCredits.delete(removedClassId);
            }
        }
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
    }
}

// 5. ページロード時PHPから渡された初期時間割データで時間割を埋める
function initializeTimetableFromPHP(data) {
    // 모든 셀 초기화 및 학점 초기화
    totalCredits = 0;
    currentTimetable = {};
    addedClassCredits = new Map(); // 초기화 시 Map도 비워줍니다.

    days.forEach(day => {
        periods.forEach(period => {
            const cell = document.getElementById(`cell-${day}-${period}`);
            if (cell) {
                cell.innerHTML = '';
                cell.classList.remove('filled-primary', 'filled-cell-wrapper');
            }
        });
    });

    if (!data || data.length === 0) {
        console.log("No initial timetable data to load.");
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
        return;
    }

    // 로드된 데이터를 기반으로 시간표 채우기
    data.forEach(item => {
        const day = item.day;
        const period = item.period;
        const cellId = `cell-${day}-${period}`;
        const targetCell = document.getElementById(cellId);

        if (targetCell) {
            targetCell.classList.add('filled-primary', 'filled-cell-wrapper');
            targetCell.innerHTML = `
                <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
                <div class="class-name-in-cell">${item.className}</div>
                <div class="class-detail-in-cell"></div>
                <div class="class-credit-in-cell">${item.classCredit}単位</div>
                <div class="term-display-in-cell">
                    ${item.classTerm == '1' ? '前期' : (item.classTerm == '2' ? '後期' : '不明')}
                </div>
            `;
            // 여기서는 각 셀의 학점을 더하는 대신,
            // 수업 ID 기준으로 한 번만 학점을 추가하도록 처리
            if (!addedClassCredits.has(item.class_id)) {
                totalCredits += item.classCredit;
                addedClassCredits.set(item.class_id, item.classCredit);
            }

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


// ... (6. confirmTimetable 함수는 그대로 유지) ...