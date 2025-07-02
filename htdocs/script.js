// PHP에서 전달받은 전역 변수들
// allClasses: 모든 수업 정보 (id, grade, term, name, category1, category2, category3, credit)
// userTimetableData: 현재 로그인된 사용자의 시간표 데이터 (class_name, credit, term 포함)
// currentUserId: 현재 로그인된 사용자 ID (null일 수 있음)
// isLoggedIn: 로그인 여부 (true/false)
// periodTimes: 교시별 시간 정보
// FIXED_CLASS_DURATION_FOR_TIMETABLE: 시간표에서 사용할 수업 지속 시간 (2시간 고정)

let selectedClass = null; // 현재 선택된 수업 정보 (class_list에서 선택 시)

// DOM 요소 캐싱
const classSearchInput = document.getElementById('classSearchInput');
const searchClassBtn = document.getElementById('searchClassBtn');
const termSelect = document.getElementById('termSelect');
const creditSelect = document.getElementById('creditSelect');
const classesTableBody = document.querySelector('#classesTable tbody');
const selectedClassInfoDiv = document.getElementById('selectedClassInfo');
const currentSelectedClassNameSpan = document.getElementById('currentSelectedClassName');
const currentSelectedClassCreditSpan = document.getElementById('currentSelectedClassCredit');
// currentSelectedClassTeacherSpan, currentSelectedClassRoomSpan, currentSelectedClassDurationOriginalSpan 제거
const currentSelectedClassDurationFixedSpan = document.getElementById('currentSelectedClassDurationFixed'); // 이것만 남음

const addSelectedClassBtn = document.getElementById('addSelectedClassBtn');
const daySelect = document.getElementById('daySelect');
const periodSelect = document.getElementById('periodSelect');
const confirmTimetableBtn = document.getElementById('confirmTimetableBtn');
const totalCreditsSpan = document.getElementById('totalCredits');
const gradeSelectFilter = document.getElementById('gradeSelectFilter');
const applyFilterBtn = document.getElementById('applyFilterBtn');

// 시간표 데이터를 Map 형태로 관리 (빠른 검색을 위함)
// key: `user_id-grade-day-period`
// value: 해당 시간표 항목의 class_id 및 기타 정보
let userTimetableMap = new Map();

// --- 초기화 함수 ---
function initializePage() {
    // URL 파라미터에서 학년 필터 초기값 설정
    const urlParams = new URLSearchParams(window.location.search);
    const initialGrade = urlParams.get('grade');
    if (initialGrade && gradeSelectFilter.querySelector(`option[value="${initialGrade}"]`)) {
        gradeSelectFilter.value = initialGrade;
    } else {
        gradeSelectFilter.value = 'all'; // 기본값 설정
    }

    // userTimetableData를 Map으로 변환
    if (userTimetableData) {
        userTimetableData.forEach(item => {
            const key = `${currentUserId}-${item.timetable_grade}-${item.day}-${item.period}`;
            // 맵에 저장할 때, 시간표에 표시될 duration은 FIXED_CLASS_DURATION_FOR_TIMETABLE로 설정
            userTimetableMap.set(key, { ...item, class_duration: FIXED_CLASS_DURATION_FOR_TIMETABLE });
        });
    }

    // PHP에서 이미 렌더링된 수업 목록 버튼에 이벤트 리스너 연결
    attachClassSelectionEvents();
    renderTimetable();    // 시간표 테이블 초기 렌더링
    updateSaveButtonState(); // 저장 버튼 상태 업데이트
}

// --- 수업 목록 필터링 및 재렌더링 함수 ---
function filterClassesTable() {
    classesTableBody.innerHTML = ''; // 기존 내용 비우기

    const searchTerm = classSearchInput.value.toLowerCase();
    const selectedTerm = termSelect.value;
    const selectedCredit = creditSelect.value;
    const selectedGradeFilter = gradeSelectFilter.value; // 전체 학년 필터

    const filteredClasses = allClasses.filter(classItem => {
        const matchesSearch = classItem.name.toLowerCase().includes(searchTerm);
        const matchesTerm = selectedTerm === 'all' || classItem.term.toString() === selectedTerm;
        const matchesCredit = selectedCredit === 'all' || classItem.credit.toString() === selectedCredit;
        const matchesGrade = selectedGradeFilter === 'all' || classItem.grade.toString() === selectedGradeFilter; // 수업의 학년과 필터 비교

        return matchesSearch && matchesTerm && matchesCredit && matchesGrade;
    });

    if (filteredClasses.length === 0) {
        const noResultsRow = document.createElement('tr');
        noResultsRow.innerHTML = `<td colspan="5" style="text-align: center;">検索結果がありません。</td>`; // 컬럼 수 변경
        classesTableBody.appendChild(noResultsRow);
        return;
    }

    filteredClasses.forEach(classItem => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${classItem.name}</td>
            <td>${classItem.grade}</td>
            <td>${classItem.term === 1 ? '前期' : '後期'}</td>
            <td>${classItem.credit}</td>
            <td>
                <button class="add-button"
                        data-class-id="${classItem.id}"
                        data-class-name="${classItem.name}"
                        data-class-credit="${classItem.credit}"
                        data-class-term="${classItem.term}"
                        data-class-grade="${classItem.grade}"
                        data-class-duration-fixed="2"> 選択
                </button>
            </td>
        `;
        classesTableBody.appendChild(row);
    });

    attachClassSelectionEvents(); // 새로 생성된 버튼에 이벤트 리스너 연결
}

// --- 수업 선택 버튼 이벤트 리스너 연결 (PHP가 렌더링한 버튼 포함) ---
function attachClassSelectionEvents() {
    document.querySelectorAll('.add-button').forEach(button => {
        // 기존 리스너 제거 (중복 방지)
        button.onclick = null;

        button.onclick = (event) => {
            const classData = event.target.dataset; // dataset으로 모든 data-* 속성 가져오기

            selectedClass = {
                id: classData.classId,
                name: classData.className,
                credit: parseInt(classData.classCredit),
                term: parseInt(classData.classTerm),
                grade: parseInt(classData.classGrade),
                // teacher_name, room_number, original_duration 필드 제거
                duration: parseInt(classData.classDurationFixed) // 시간표에 들어갈 고정 duration (2)
            };

            currentSelectedClassNameSpan.textContent = selectedClass.name;
            currentSelectedClassCreditSpan.textContent = selectedClass.credit;
            // currentSelectedClassTeacherSpan, currentSelectedClassRoomSpan, currentSelectedClassDurationOriginalSpan 제거
            currentSelectedClassDurationFixedSpan.textContent = selectedClass.duration; // 항상 2
            selectedClassInfoDiv.style.display = 'block';
        };
    });
}

// --- 시간표 렌더링 함수 ---
function renderTimetable() {
    const timetableBody = document.querySelector('#timetable tbody');
    timetableBody.innerHTML = ''; // 기존 시간표 내용 비우기

    const days = ['月', '火', '水', '木', '金', '土']; // 요일 배열 (행의 헤더)

    const selectedFilterGrade = parseInt(gradeSelectFilter.value); // 현재 선택된 필터 학년

    // 각 요일별로 행을 생성
    days.forEach(day => {
        const row = document.createElement('tr');

        // 첫 번째 셀: 요일 헤더 (예: "月")
        const dayHeaderCell = document.createElement('td');
        dayHeaderCell.textContent = day + '曜日'; // "月曜日"
        row.appendChild(dayHeaderCell);

        // 해당 요일의 각 교시 셀들을 처리
        let currentPeriod = 1;
        while (currentPeriod <= 10) {
            const key = `${currentUserId}-${selectedFilterGrade}-${day}-${currentPeriod}`;
            const classInfo = userTimetableMap.get(key);

            let rendered = false;
            if (classInfo && classInfo.class_id) {
                // 이 칸이 수업의 시작점인지 확인
                // Map에는 수업의 시작 교시에만 데이터가 저장되어 있음
                // 만약 이 칸이 수업의 시작 교시라면, 수업 지속 시간만큼 병합하여 렌더링
                if (classInfo.period === currentPeriod) { // 저장된 period가 현재 currentPeriod와 일치하는지 확인
                    const duration = FIXED_CLASS_DURATION_FOR_TIMETABLE; // 수업 지속 시간 2로 고정
                    const cell = document.createElement('td');
                    cell.colSpan = duration; // span 속성 적용
                    cell.classList.add('filled-primary');
                    cell.innerHTML = `
                        <div class="time-slot"
                            data-day="${day}"
                            data-period="${currentPeriod}"
                            data-class-id="${classInfo.class_id}"
                            data-grade="${selectedFilterGrade}"
                            data-duration="${duration}">
                            <span class="class-name-in-cell">${classInfo.class_name || ''}</span>
                            <span class="class-credit-in-cell">${classInfo.credit || 0}単位</span>
                            <span class="term-display-in-cell">${classInfo.term === 1 ? '前期' : '後期'}</span>
                            <button class="remove-button">X</button>
                        </div>
                    `;
                    row.appendChild(cell);
                    currentPeriod += duration; // 다음 교시로 이동 (duration만큼 건너뛰기)
                    rendered = true;
                }
            }

            if (!rendered) {
                // 수업이 등록되어 있지 않거나, 이미 병합된 셀의 일부인 경우 비어있는 칸 생성
                let isPartOfMergedClass = false;
                for (let p = 1; p < currentPeriod; p++) {
                    const prevKey = `${currentUserId}-${selectedFilterGrade}-${day}-${p}`;
                    const prevClassInfo = userTimetableMap.get(prevKey);
                    // 이전 수업이 현재 칸을 포함하는지 확인
                    if (prevClassInfo && prevClassInfo.class_id && prevClassInfo.period + FIXED_CLASS_DURATION_FOR_TIMETABLE > currentPeriod) {
                        isPartOfMergedClass = true;
                        break;
                    }
                }

                if (!isPartOfMergedClass) {
                    const cell = document.createElement('td');
                    cell.innerHTML = `
                        <div class="time-slot"
                            data-day="${day}"
                            data-period="${currentPeriod}"
                            data-class-id=""
                            data-grade="${selectedFilterGrade}"
                            data-duration="1"></div>
                    `;
                    row.appendChild(cell);
                    currentPeriod++; // 다음 교시로 이동
                } else {
                    currentPeriod++; // 병합된 부분은 건너뛰고 다음 교시로
                }
            }
        }
        timetableBody.appendChild(row);
    });

    attachTimetableCellClickEvents(); // 새로 생성된 셀에 이벤트 리스너 다시 연결
    updateTotalCredits(); // 총 학점 업데이트 함수 호출
}


// --- 시간표 셀 클릭 이벤트 리스너 연결 ---
function attachTimetableCellClickEvents() {
    document.querySelectorAll('.time-slot').forEach(timeSlotDiv => {
        // 기존 리스너 제거 (중복 방지)
        timeSlotDiv.onclick = null;
        timeSlotDiv.querySelector('.remove-button')?.onclick = null;

        // 셀 클릭 (수업 추가 또는 선택된 수업 지우기)
        timeSlotDiv.onclick = (event) => {
            // X 버튼 클릭은 별도로 처리하므로, 부모 div 클릭 시에는 무시
            if (event.target.classList.contains('remove-button')) {
                return;
            }

            const day = timeSlotDiv.dataset.day;
            const period = parseInt(timeSlotDiv.dataset.period);
            const currentGrade = parseInt(timeSlotDiv.dataset.grade); // 현재 시간표의 학년
            const currentClassIdInSlot = timeSlotDiv.dataset.classId;

            if (selectedClass) {
                // 선택된 수업이 있다면, 해당 칸에 추가 시도
                if (isLoggedIn) {
                    addOrUpdateTimetableEntry(selectedClass.id, day, period, currentGrade, selectedClass.duration); // selectedClass.duration은 2임
                } else {
                    alert('ログインして時間割を編集してください。');
                }
            } else if (currentClassIdInSlot) {
                // 선택된 수업은 없지만, 칸에 수업이 이미 있다면 해당 수업 정보를 선택된 수업으로 표시
                const existingClass = allClasses.find(cls => cls.id.toString() === currentClassIdInSlot);
                if (existingClass) {
                    selectedClass = {
                        id: existingClass.id,
                        name: existingClass.name,
                        credit: existingClass.credit,
                        term: existingClass.term,
                        grade: existingClass.grade,
                        // teacher_name, room_number, original_duration 필드 제거
                        duration: FIXED_CLASS_DURATION_FOR_TIMETABLE // 시간표에 들어갈 고정 duration (2)
                    };
                    currentSelectedClassNameSpan.textContent = selectedClass.name;
                    currentSelectedClassCreditSpan.textContent = selectedClass.credit;
                    // currentSelectedClassTeacherSpan, currentSelectedClassRoomSpan, currentSelectedClassDurationOriginalSpan 제거
                    currentSelectedClassDurationFixedSpan.textContent = selectedClass.duration;
                    selectedClassInfoDiv.style.display = 'block';
                }
            } else {
                // 선택된 수업도 없고, 칸도 비어있으면 아무것도 하지 않음 (또는 메시지 표시)
                alert('授業一覧から授業を選択してください。');
            }
        };

        // 삭제 버튼 클릭 이벤트
        const removeButton = timeSlotDiv.querySelector('.remove-button');
        if (removeButton) {
            removeButton.onclick = (event) => {
                event.stopPropagation(); // 셀 클릭 이벤트와 중복 방지
                if (isLoggedIn) {
                    const day = timeSlotDiv.dataset.day;
                    const period = parseInt(timeSlotDiv.dataset.period);
                    const currentGrade = parseInt(timeSlotDiv.dataset.grade); // 현재 시간표의 학년
                    removeTimetableEntry(day, period, currentGrade);
                } else {
                    alert('ログインして時間割を編集してください。');
                }
            };
        }
    });
}


// --- 선택된 수업을 시간표에 추가 버튼 클릭 이벤트 ---
addSelectedClassBtn.addEventListener('click', () => {
    if (selectedClass) {
        if (isLoggedIn) {
            const day = daySelect.value;
            const period = parseInt(periodSelect.value);
            const currentGrade = parseInt(gradeSelectFilter.value); // 현재 필터링된 학년을 저장할 학년으로 사용
            addOrUpdateTimetableEntry(selectedClass.id, day, period, currentGrade, selectedClass.duration); // selectedClass.duration은 2임
        } else {
            alert('ログインして時間割を編集してください。');
        }
    } else {
        alert('授業一覧から追加する授業を選択してください。');
    }
});


// --- 시간표 항목 추가 또는 업데이트 함수 (duration 인자 사용) ---
function addOrUpdateTimetableEntry(classId, startDay, startPeriod, grade, duration) {
    // 수업이 들어갈 칸 범위 확인
    for (let p = startPeriod; p < startPeriod + duration; p++) {
        if (p > 10) { // 10교시를 넘어가면 추가 불가
            alert('選択した授業は時間割の範囲を超えます。');
            return;
        }
        const key = `${currentUserId}-${grade}-${startDay}-${p}`;
        const existingEntry = userTimetableMap.get(key);
        if (existingEntry && existingEntry.class_id && existingEntry.class_id !== classId.toString()) {
            // 해당 범위에 이미 다른 수업이 있다면 추가 불가
            alert(`${startDay}曜日 ${p}限にはすでに授業が登録されています。`);
            return;
        }
    }

    // 새롭게 추가할 classItem 정보 찾기 (8개 컬럼만 포함)
    const classToAdd = allClasses.find(cls => cls.id.toString() === classId.toString());
    if (!classToAdd) {
        alert('選択された授業情報が見つかりません。');
        return;
    }

    // 기존에 동일한 수업이 시간표에 있다면 모두 삭제
    for (let p = 1; p <= 10; p++) {
        const checkKey = `${currentUserId}-${grade}-${startDay}-${p}`;
        const entry = userTimetableMap.get(checkKey);
        if (entry && entry.class_id === classId.toString()) {
             userTimetableMap.delete(checkKey);
        }
    }

    // 맵에 수업 정보를 추가 (시작 교시에만 등록하고, 나머지 교시는 렌더링 시 처리)
    // Map에 저장할 때는 8개 컬럼 정보만 저장
    userTimetableMap.set(`${currentUserId}-${grade}-${startDay}-${startPeriod}`, {
        user_id: currentUserId,
        timetable_grade: grade,
        day: startDay,
        period: startPeriod,
        class_id: classId,
        class_name: classToAdd.name,
        credit: classToAdd.credit,
        term: classToAdd.term,
        // teacher_name, room_number, original_class_duration 필드 제거
        class_duration: duration // 시간표에서의 고정된 지속 시간 (2)
    });

    renderTimetable(); // 시간표 다시 그리기
    selectedClass = null; // 선택된 수업 초기화
    selectedClassInfoDiv.style.display = 'none'; // 선택 정보 숨기기
}


// --- 시간표 항목 제거 함수 ---
function removeTimetableEntry(day, period, grade) {
    // 클릭한 칸이 포함된 수업의 '시작' 교시를 찾아야 함
    let classIdToRemove = null;
    let startPeriodOfClass = -1;
    let durationOfClass = FIXED_CLASS_DURATION_FOR_TIMETABLE; // 2시간 고정

    // 현재 교시(period)를 포함하는 수업의 시작 교시를 찾기
    for (let p = 1; p <= period; p++) {
        const key = `${currentUserId}-${grade}-${day}-${p}`;
        const entry = userTimetableMap.get(key);
        if (entry && entry.class_id) {
            // 이 entry가 현재 period를 포함하는 수업의 시작점이라면
            if (entry.period + durationOfClass > period && entry.period <= period) { // 시작 교시 + 지속 시간이 현재 클릭한 교시보다 크고, 시작 교시가 현재 교시보다 작거나 같으면
                classIdToRemove = entry.class_id;
                startPeriodOfClass = entry.period;
                break;
            }
        }
    }

    if (classIdToRemove && startPeriodOfClass !== -1) {
        // 해당 수업이 차지하는 모든 칸에서 맵 엔트리 제거
        // 실제 Map에는 시작 교시에만 데이터가 있으므로, 해당 시작 교시만 삭제
        userTimetableMap.delete(`${currentUserId}-${grade}-${day}-${startPeriodOfClass}`);
        renderTimetable(); // 시간표 다시 그리기
        selectedClass = null; // 선택된 수업 초기화
        selectedClassInfoDiv.style.display = 'none'; // 선택 정보 숨기기
    }
}

// --- 총 학점 업데이트 함수 ---
function updateTotalCredits() {
    let total = 0;
    const selectedFilterGrade = parseInt(gradeSelectFilter.value); // 현재 선택된 필터 학년

    // 이미 계산된 수업을 추적하여 중복 합산 방지 (수업의 시작점만 카운트)
    const countedClassesStartPoints = new Set();

    userTimetableMap.forEach(item => {
        // 현재 필터링된 학년의 수업만 학점 계산에 포함
        if (item.user_id === currentUserId && item.timetable_grade === selectedFilterGrade) {
             // 이 수업이 Map에 저장된 '시작점'인지 확인 (중복 카운트 방지)
             const startPointKey = `${item.class_id}-${item.day}-${item.period}`;
             if (!countedClassesStartPoints.has(startPointKey)) {
                 total += item.credit;
                 countedClassesStartPoints.add(startPointKey);
             }
        }
    });
    totalCreditsSpan.textContent = `合計単位数: ${total}`;
}

// --- 시간표 저장 (서버로 데이터 전송) 함수 ---
confirmTimetableBtn.addEventListener('click', () => {
    if (!isLoggedIn) {
        alert('ログインしてください。');
        window.location.href = 'login.php';
        return;
    }

    const currentGradeToSave = parseInt(gradeSelectFilter.value); // 현재 선택된 학년 필터 값을 저장할 학년으로 사용

    const timetableDataToSave = [];
    const processedClasses = new Set(); // 중복 저장 방지를 위한 Set (수업의 시작점만 포함)

    // userTimetableMap에서 현재 학년의 시간표 데이터만 추출
    userTimetableMap.forEach(item => {
        if (item.user_id === currentUserId && item.timetable_grade === currentGradeToSave) {
            const classKey = `${item.class_id}-${item.day}-${item.period}`; // 수업의 시작점만 저장

            if (!processedClasses.has(classKey)) {
                timetableDataToSave.push({
                    class_id: item.class_id,
                    day: item.day,
                    period: item.period, // 시작 교시만 전달
                    grade: item.timetable_grade // user_timetables에 저장될 grade
                });
                processedClasses.add(classKey);
            }
        }
    });

    // 서버로 데이터 전송 (AJAX)
    fetch('save_timetable.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            userId: currentUserId,
            grade: currentGradeToSave, // 현재 저장하려는 학년
            timetableData: timetableDataToSave
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert(data.message);
            // 성공적으로 저장되면 시간표만 다시 렌더링하도록 변경하여 부드러운 UX 제공
            renderTimetable();
        } else {
            alert(data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('時間割の保存中にエラーが発生しました。');
    });
});

// --- 저장 버튼 활성화/비활성화 (로그인 여부에 따라) ---
function updateSaveButtonState() {
    if (isLoggedIn) {
        confirmTimetableBtn.classList.remove('disabled-button');
        confirmTimetableBtn.disabled = false;
    } else {
        confirmTimetableBtn.classList.add('disabled-button');
        confirmTimetableBtn.disabled = true;
    }
}

// --- 이벤트 리스너 ---
searchClassBtn.addEventListener('click', filterClassesTable);
termSelect.addEventListener('change', filterClassesTable);
creditSelect.addEventListener('change', filterClassesTable);
classSearchInput.addEventListener('input', filterClassesTable); // 실시간 검색
gradeSelectFilter.addEventListener('change', () => {
    filterClassesTable(); // 수업 목록 필터링
    renderTimetable();    // 시간표 다시 그리기 (학년 변경 시)
});
applyFilterBtn.addEventListener('click', () => {
    filterClassesTable(); // 수업 목록 필터링
    renderTimetable();    // 시간표 다시 그리기 (학년 변경 시)
});


// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', initializePage);