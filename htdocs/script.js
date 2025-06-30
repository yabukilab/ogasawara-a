//// 전역 변수
let selectedClass = null; // 현재 선택된 수업 정보를 저장
let currentTimetable = {}; // 현재 사용자의 시간표 데이터를 저장 {day: {period: classId}}
let totalCredits = 0; // 총 학점

// 요일과 시차 매핑 (편의를 위해 배열로 정의)
const days = ['月', '火', '水', '木', '金', '土'];
const periods = Array.from({length: 10}, (_, i) => i + 1); // 1부터 10까지


// 1. 페이지 로드 시 현재 필터 상태 표시 (index.php에서 호출)
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


// 2. 수업 목록에서 수업 선택 시 정보 표시
function selectClass(buttonElement) {
    const row = buttonElement.closest('tr');
    selectedClass = {
        id: row.dataset.classId,
        name: row.dataset.className,
        credit: parseInt(row.dataset.classCredit),
        term: row.dataset.classTerm,
        grade: row.dataset.classGrade
    };

    document.getElementById('currentSelectedClassName').textContent = selectedClass.name;
    document.getElementById('currentSelectedClassCredit').textContent = selectedClass.credit;
}


// 3. 시간표에 수업 추가 (HTML의 "時間割に追加" 버튼 클릭 시 호출)
function addClassToTimetable() {
    if (!selectedClass) {
        alert('時間割に追加する授業を選択してください。');
        return;
    }

    const day = document.getElementById('day_select').value;
    const period = document.getElementById('time_select').value;

    const cellId = `cell-${day}-${period}`;
    const targetCell = document.getElementById(cellId);

    if (!targetCell) {
        console.error(`Error: Timetable cell with ID ${cellId} not found.`);
        alert('時間割のセルが見つかりません。');
        return;
    }

    // 이미 수업이 있는 경우
    if (targetCell.classList.contains('filled-primary')) {
        const confirmOverwrite = confirm('この時限にはすでに授業があります。上書きしますか？');
        if (!confirmOverwrite) {
            return;
        }
        // 기존 수업 정보 제거 (학점 감소)
        const existingCreditElement = targetCell.querySelector('.class-credit-in-cell');
        if (existingCreditElement) {
            const existingCredit = parseInt(existingCreditElement.textContent.replace('単位', ''));
            totalCredits -= existingCredit;
        }
        targetCell.innerHTML = ''; // 기존 내용 제거
    }

    // 새로운 수업 정보 추가
    targetCell.classList.add('filled-primary');
    targetCell.innerHTML = `
        <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
        <div class="class-name-in-cell">${selectedClass.name}</div>
        <div class="class-credit-in-cell">${selectedClass.credit}単位</div>
        <div class="term-display-in-cell">
            ${selectedClass.term == '1' ? '前期' : (selectedClass.term == '2' ? '後期' : '不明')}
        </div>
        `;

    // 총 학점 업데이트
    totalCredits += selectedClass.credit;
    document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;

    // currentTimetable 객체 업데이트
    if (!currentTimetable[day]) {
        currentTimetable[day] = {};
    }
    currentTimetable[day][period] = {
        class_id: selectedClass.id,
        className: selectedClass.name,
        classCredit: selectedClass.credit,
        classTerm: selectedClass.term,
        classGrade: selectedClass.grade // 현재 학년 저장 (필터와 동일)
    };

    // 선택된 수업 정보 초기화
    selectedClass = null;
    document.getElementById('currentSelectedClassName').textContent = 'なし';
    document.getElementById('currentSelectedClassCredit').textContent = '0';
}

// 4. 시간표에서 수업 제거
function removeClassFromTimetable(day, period) {
    const cellId = `cell-${day}-${period}`;
    const targetCell = document.getElementById(cellId);

    if (targetCell && targetCell.classList.contains('filled-primary')) {
        const confirmRemove = confirm('この授業を時間割から削除しますか？');
        if (!confirmRemove) {
            return;
        }

        // 기존 수업의 학점을 총 학점에서 제외
        const classCredit = currentTimetable[day][period].classCredit;
        totalCredits -= classCredit;
        document.getElementById('totalCredits').textContent = `合計単位数: ${totalCredits}`;

        // 셀 내용 초기화 및 클래스 제거
        targetCell.innerHTML = '';
        targetCell.classList.remove('filled-primary');

        // currentTimetable 객체에서 제거
        delete currentTimetable[day][period];
        if (Object.keys(currentTimetable[day]).length === 0) {
            delete currentTimetable[day]; // 해당 요일에 수업이 없으면 요일 객체도 제거
        }
    }
}


// 5. 페이지 로드 시 PHP에서 전달받은 초기 시간표 데이터로 시간표 채우기
function initializeTimetableFromPHP(data) {
    if (!data || data.length === 0) {
        console.log("No initial timetable data to load.");
        return;
    }

    totalCredits = 0; // 초기화
    currentTimetable = {}; // 초기화

    data.forEach(item => {
        const day = item.day;
        const period = item.period;
        const cellId = `cell-${day}-${period}`;
        const targetCell = document.getElementById(cellId);

        if (targetCell) {
            targetCell.classList.add('filled-primary');
            targetCell.innerHTML = `
                <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
                <div class="class-name-in-cell">${item.className}</div>
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


// 6. 시간표 확정 및 서버 저장
function confirmTimetable() {
    if (!isUserLoggedIn) {
        alert('時間割を保存するにはログインが必要です。');
        window.location.href = 'login.php'; // 로그인 페이지로 리디렉션
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
                        grade: currentSelectedGradeFromPHP // 현재 필터링된 학년 값을 저장
                    });
                }
            }
        }
    }

    if (timetableToSave.length === 0) {
        alert('保存する授業がありません。時間割に授業を追加してください。');
        return;
    }

    if (!confirm(`現在の時間割 (${totalCredits}単位) を保存しますか？`)) {
        return;
    }

    fetch('save_timetable.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            userId: currentLoggedInUserId,
            grade: currentSelectedGradeFromPHP, // 현재 학년 값 전달
            timetableData: timetableToSave
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert('時間割が正常に保存されました！');
            // 저장 후 필요하다면 페이지를 새로고침하거나 UI 업데이트
            // window.location.reload();
        } else {
            alert('時間割の保存に失敗しました: ' + data.message);
            console.error('Save failed:', data.message);
        }
    })
    .catch(error => {
        alert('時間割の保存中にエラーが発生しました。');
        console.error('Error saving timetable:', error);
    });
}