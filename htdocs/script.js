// 전역 변수 (시간표 상태를 저장)
let selectedClass = null; // 현재 선택된 수업 정보
let currentTimetable = {}; // {day: {period: {classData}}} 형태 (화면에 표시될 시간표 데이터)
let totalCredits = 0; // 총 학점

// PHP에서 넘어온 초기 데이터를 사용하여 시간표를 초기화하는 함수
function initializeTimetableFromPHP(initialData) {
    currentTimetable = {}; // 시간표 초기화
    initialData.forEach(item => {
        if (!currentTimetable[item.day]) {
            currentTimetable[item.day] = {};
        }
        // is_primary는 이제 PHP에서 넘어오지 않으므로 저장하지 않음
        currentTimetable[item.day][item.period] = {
            class_id: item.class_id,
            className: item.className,
            classCredit: item.classCredit,
            classTerm: item.classTerm,
            classGrade: item.classGrade
        };
    });
    renderTimetable(); // 시간표 UI를 다시 그림
    updateDisplayTotalCredits(); // 총 학점 업데이트
}

// 총 학점 계산 및 표시 업데이트
function updateDisplayTotalCredits() {
    totalCredits = 0;
    const calculatedClassIds = new Set(); // 학점 중복 계산 방지

    for (const day in currentTimetable) {
        for (const period in currentTimetable[day]) {
            const classEntry = currentTimetable[day][period];
            // is_primary 확인 없이, 고유한 class_id에 대해서만 학점 계산
            if (!calculatedClassIds.has(classEntry.class_id)) {
                totalCredits += parseInt(classEntry.classCredit);
                calculatedClassIds.add(classEntry.class_id);
            }
        }
    }
    document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;
}


// 수업 목록에서 수업을 선택했을 때 호출되는 함수
function selectClass(buttonElement) {
    const row = buttonElement.closest('tr');
    selectedClass = {
        id: row.dataset.classId,
        name: row.dataset.className,
        credit: parseInt(row.dataset.classCredit),
        term: parseInt(row.dataset.classTerm),
        grade: parseInt(row.dataset.classGrade)
    };

    // 선택된 수업 정보 표시 업데이트
    document.getElementById('currentSelectedClassName').textContent = selectedClass.name;
    document.getElementById('currentSelectedClassCredit').textContent = selectedClass.credit;
}

// 시간표에 수업을 추가하는 함수
function addClassToTimetable() {
    console.log("addClassToTimetable called!");
    console.log("selectedClass:", selectedClass);

    if (!selectedClass) {
        alert("まず授業を選択してください。"); // 먼저 수업을 선택하세요
        return;
    }

    const selectedDay = document.getElementById('day_select').value;
    const selectedPeriod = parseInt(document.getElementById('time_select').value);
    const nextPeriod = selectedPeriod + 1; // 모든 수업은 2시간이므로 다음 교시도 필요

    console.log("Selected Day:", selectedDay, "Selected Period:", selectedPeriod, "Next Period:", nextPeriod);
    console.log("Current Timetable state for this slot:", currentTimetable[selectedDay] ? currentTimetable[selectedDay][selectedPeriod] : "Empty");
    console.log("Current Timetable state for next slot:", currentTimetable[selectedDay] ? currentTimetable[selectedDay][nextPeriod] : "Empty");


    // 현재 선택된 학년과 수업의 학년이 다르면 경고
    if (selectedClass.grade !== currentSelectedGradeFromPHP) {
        alert(`選択した授業は${selectedClass.grade}年生の授業です。現在の表示学年(${currentSelectedGradeFromPHP}年生)とは異なります。`);
        return;
    }

    // 현재 선택된 학기와 수업의 학기가 다르면 경고 (필터가 '全て'가 아닐 경우)
    if (currentSelectedTermFromPHP !== '0' && selectedClass.term !== parseInt(currentSelectedTermFromPHP)) {
        alert(`選択した授業は${getTermName(selectedClass.term)}の授業です。現在の表示学期(${getTermName(parseInt(currentSelectedTermFromPHP))})とは異なります。`);
        return;
    }

    // 2시간 수업이므로, 다음 시한이 유효한 범위 내에 있는지 확인
    if (nextPeriod > 10) {
        alert("2時間授業のため、選択した時限の次に利用可能な時限が必要です。"); // 2시간 수업이므로, 선택한 시한 다음에 이용 가능한 시한이 필요합니다.
        return;
    }

    // 선택된 두 칸(현재 시한과 다음 시한)이 모두 비어있는지 확인
    if ((currentTimetable[selectedDay] && currentTimetable[selectedDay][selectedPeriod]) ||
        (currentTimetable[selectedDay] && currentTimetable[selectedDay][nextPeriod])) {
        alert("選択した時間帯（2時間連続）はすでに他の授業で埋まっているか、一部が埋まっています。"); // 선택한 시간대(2시간 연속)는 이미 다른 수업으로 채워져 있거나, 일부가 채워져 있습니다.
        return;
    }

    // 시간표 데이터 업데이트 (두 칸 모두에 동일한 수업 정보 저장)
    if (!currentTimetable[selectedDay]) {
        currentTimetable[selectedDay] = {};
    }
    currentTimetable[selectedDay][selectedPeriod] = {
        class_id: selectedClass.id,
        className: selectedClass.name,
        classCredit: selectedClass.credit,
        classTerm: selectedClass.term,
        classGrade: selectedClass.grade
    };
    currentTimetable[selectedDay][nextPeriod] = {
        class_id: selectedClass.id,
        className: selectedClass.name,
        classCredit: selectedClass.credit,
        classTerm: selectedClass.term,
        classGrade: selectedClass.grade
    };

    renderTimetable(); // UI 업데이트
    updateDisplayTotalCredits(); // 총 학점 업데이트
    selectedClass = null; // 수업 선택 해제
    document.getElementById('currentSelectedClassName').textContent = 'なし';
    document.getElementById('currentSelectedClassCredit').textContent = '0';
}

// 시간표에서 수업을 제거하는 함수
function removeClassFromTimetable(buttonElement) {
    const cell = buttonElement.closest('td');
    const day = cell.dataset.day;
    const period = parseInt(cell.dataset.time);
    const classIdToRemove = cell.dataset.classId;

    if (!confirm("この授業を時間割から削除しますか？ (2時間連続で削除されます)")) { // 이 수업을 시간표에서 삭제하시겠습니까? (2시간 연속으로 삭제됩니다)
        return;
    }

    // 현재 칸의 수업과 연결된 다른 칸을 찾아서 함께 삭제
    // 2시간 수업이므로, 현재 칸이 P라면 P-1 또는 P+1에 같은 수업이 있을 것
    const classEntryAtCurrentPeriod = currentTimetable[day] ? currentTimetable[day][period] : null;

    if (classEntryAtCurrentPeriod && classEntryAtCurrentPeriod.class_id === classIdToRemove) {
        // 현재 칸 삭제
        delete currentTimetable[day][period];

        // 다음 칸이 같은 수업인지 확인하고 삭제
        const nextPeriod = period + 1;
        if (currentTimetable[day] && currentTimetable[day][nextPeriod] &&
            currentTimetable[day][nextPeriod].class_id === classIdToRemove) {
            delete currentTimetable[day][nextPeriod];
        }

        // 이전 칸이 같은 수업인지 확인하고 삭제
        const prevPeriod = period - 1;
        if (currentTimetable[day] && currentTimetable[day][prevPeriod] &&
            currentTimetable[day][prevPeriod].class_id === classIdToRemove) {
            delete currentTimetable[day][prevPeriod];
        }
    }

    // 해당 요일에 수업이 더 이상 없으면 요일 객체도 삭제
    if (currentTimetable[day] && Object.keys(currentTimetable[day]).length === 0) {
        delete currentTimetable[day];
    }

    renderTimetable();
    updateDisplayTotalCredits();
}

// 시간표 UI 렌더링 함수
function renderTimetable() {
    const timetableTable = document.getElementById('timetable');
    const tbody = timetableTable.querySelector('tbody');
    tbody.innerHTML = ''; // 기존 내용 지우기

    const times = [
        1, 2, 3, 4, 5, 6, 7, 8, 9, 10
    ];
    const days_of_week = ['月', '火', '水', '木', '金', '土'];

    times.forEach(period => {
        const row = document.createElement('tr');
        // 시한 셀
        const periodCell = document.createElement('td');
        const timeRange = {
            1: '9:00-10:00', 2: '10:00-11:00', 3: '11:00-12:00',
            4: '13:00-14:00', 5: '14:00-15:00', 6: '15:00-16:00',
            7: '16:00-17:00', 8: '17:00-18:00', 9: '18:00-19:00', 10: '19:00-20:00'
        }[period];
        periodCell.innerHTML = `${period}限<br><span style='font-size:0.8em; color:#666;'>${timeRange.split('-')[0]}</span>`;
        row.appendChild(periodCell);

        days_of_week.forEach(day => {
            const cell = document.createElement('td');
            cell.classList.add('time-slot');
            cell.dataset.day = day; // data-day 속성 사용
            cell.dataset.time = period;

            const classData = currentTimetable[day] ? currentTimetable[day][period] : null;

            if (classData) {
                cell.dataset.classId = classData.class_id;
                cell.dataset.className = classData.className;
                cell.dataset.classCredit = classData.classCredit;
                cell.dataset.classTerm = classData.classTerm;
                cell.dataset.classGrade = classData.classGrade;
                // is_primary는 이제 사용하지 않으므로 dataset에 추가하지 않음

                const cellContent = `${classData.className}<br>(${classData.classCredit}単位)`;
                cell.classList.add('filled-primary'); // 모든 채워진 셀은 primary로 표시

                const termDisplay = `<div class='term-display-in-cell'>${getTermName(classData.classTerm)}</div>`;
                cell.innerHTML = `${cellContent}${termDisplay}<button class='remove-button' onclick='removeClassFromTimetable(this)'>X</button>`;
            }
            row.appendChild(cell);
        });
        tbody.appendChild(row);
    });
}

// 필터 표시 업데이트 (학년/학기 드롭다운 아래에 현재 필터 값 표시)
function updateFilterDisplay() {
    const termNames = {
        '0': '全て',
        '1': '前期',
        '2': '後期'
    };
    document.getElementById('displayGrade').textContent = `${currentSelectedGradeFromPHP}年生`;
    document.getElementById('displayTerm').textContent = termNames[currentSelectedTermFromPHP];
}

// getTermName JavaScript 버전 (PHP에서 직접 넘겨받지 않을 경우 대비)
function getTermName(term_num) {
    switch (term_num) {
        case 1: return '前期';
        case 2: return '後期';
        case 0: return '全て'; // 모든 학기 필터
        default: return '不明';
    }
}


// 시간표 확정 버튼 클릭 시 호출
function confirmTimetable() {
    if (Object.keys(currentTimetable).length === 0) {
        alert("時間割に授業が登録されていません。"); // 시간표에 수업이 등록되어 있지 않습니다.
        return;
    }

    if (!confirm("この時間割で登録を確定しますか？現在の時間割は上書きされます。")) { // 이 시간표로 등록을 확정하시겠습니까? 현재 시간표는 덮어쓰여집니다.
        return;
    }

    const timetableToSend = [];
    const sentClassPeriods = new Set(); // 중복 전송 방지를 위한 Set

    for (const day in currentTimetable) {
        for (const period in currentTimetable[day]) {
            const classData = currentTimetable[day][period];
            // 각 (day, period) 쌍을 고유하게 식별하여 중복 전송 방지
            const uniqueKey = `${day}-${period}-${classData.class_id}`;
            if (!sentClassPeriods.has(uniqueKey)) {
                timetableToSend.push({
                    student_number: currentLoggedInStudentNumber,
                    grade: currentSelectedGradeFromPHP,
                    day: day,
                    period: period,
                    class_id: classData.class_id,
                    is_primary: 0 // is_primary 컬럼이 DB에 남아있을 경우를 대비하여 0으로 전송
                });
                sentClassPeriods.add(uniqueKey);
            }
        }
    }

    // 서버에 시간표 데이터 전송 (AJAX 사용)
    fetch('save_timetable.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(timetableToSend),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("時間割が正常に登録されました！"); // 시간표가 정상적으로 등록되었습니다!
            // 성공 후 확정된 시간표 페이지로 이동
            window.location.href = `confirmed_timetable.php?grade_filter=${currentSelectedGradeFromPHP}`;
        } else {
            alert("時間割の登録に失敗しました: " + data.message); // 시간표 등록에 실패했습니다:
            console.error("Error details:", data.error_details);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert("通信エラーにより時間割の登録に失敗しました。"); // 통신 오류로 인해 시간표 등록에 실패했습니다.
    });
}