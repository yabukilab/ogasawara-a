// PHP에서 전달받은 전역 변수들
// allClasses: 모든 수업 정보
// userTimetableData: 현재 로그인된 사용자의 시간표 데이터
// currentUserId: 현재 로그인된 사용자 ID (null일 수 있음)
// isLoggedIn: 로그인 여부 (true/false)

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
const addSelectedClassBtn = document.getElementById('addSelectedClassBtn');
const daySelect = document.getElementById('daySelect');
const periodSelect = document.getElementById('periodSelect');
const confirmTimetableBtn = document.getElementById('confirmTimetableBtn');
const totalCreditsSpan = document.getElementById('totalCredits');
const gradeSelectFilter = document.getElementById('gradeSelectFilter');
const applyFilterBtn = document.getElementById('applyFilterBtn');

// 시간표 데이터를 Map 형태로 관리 (빠른 검색을 위함)
// key: `user_id-grade-day-period` (PHP의 복합 PK와 일치)
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
            const key = `${item.user_id}-${item.timetable_grade}-${item.day}-${item.period}`;
            userTimetableMap.set(key, item);
        });
    }

    renderClassesTable(); // 수업 목록 테이블 초기 렌더링
    renderTimetable();    // 시간표 테이블 초기 렌더링
    updateSaveButtonState(); // 저장 버튼 상태 업데이트
}

// --- 수업 목록 렌더링 함수 ---
function renderClassesTable() {
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
        noResultsRow.innerHTML = `<td colspan="5" style="text-align: center;">検索結果がありません。</td>`;
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
                <button class="add-button" data-class-id="${classItem.id}"
                        data-class-name="${classItem.name}"
                        data-class-credit="${classItem.credit}"
                        data-class-term="${classItem.term}"
                        data-class-grade="${classItem.grade}"
                        data-class-teacher="${classItem.teacher_name || ''}"
                        data-class-room="${classItem.room_number || ''}">選択</button>
            </td>
        `;
        classesTableBody.appendChild(row);
    });

    attachClassSelectionEvents(); // 새로 생성된 버튼에 이벤트 리스너 연결
}

// --- 수업 선택 버튼 이벤트 리스너 연결 ---
function attachClassSelectionEvents() {
    document.querySelectorAll('.add-button').forEach(button => {
        button.onclick = (event) => {
            const classId = event.target.dataset.classId;
            const className = event.target.dataset.className;
            const classCredit = event.target.dataset.classCredit;
            const classTerm = event.target.dataset.classTerm;
            const classGrade = event.target.dataset.classGrade;
            const classTeacher = event.target.dataset.classTeacher;
            const classRoom = event.target.dataset.classRoom;

            selectedClass = {
                id: classId,
                name: className,
                credit: parseInt(classCredit),
                term: parseInt(classTerm),
                grade: parseInt(classGrade),
                teacher_name: classTeacher, // DB에서 이 컬럼을 가져오는지 확인 필요
                room_number: classRoom      // DB에서 이 컬럼을 가져오는지 확인 필요
            };

            currentSelectedClassNameSpan.textContent = selectedClass.name;
            currentSelectedClassCreditSpan.textContent = selectedClass.credit;
            selectedClassInfoDiv.style.display = 'block';
        };
    });
}

// --- 시간표 렌더링 함수 (핵심 변경 부분) ---
function renderTimetable() {
    const timetableBody = document.querySelector('#timetable tbody');
    timetableBody.innerHTML = ''; // 기존 시간표 내용 비우기

    const days = ['月', '火', '水', '木', '金', '土']; // 요일 배열
    const periods = Array.from({ length: 10 }, (_, i) => i + 1); // 1교시부터 10교시

    const selectedFilterGrade = parseInt(gradeSelectFilter.value); // 현재 선택된 필터 학년

    periods.forEach(period => {
        const row = document.createElement('tr');

        // 첫 번째 셀: 시한 번호 (예: "1", "2")
        const periodHeaderCell = document.createElement('td');
        periodHeaderCell.textContent = period;
        row.appendChild(periodHeaderCell);

        days.forEach(day => {
            const cell = document.createElement('td');
            const classInfo = userTimetableMap.get(`${currentUserId}-${selectedFilterGrade}-${day}-${period}`);

            // 수업이 등록되어 있으면 내용을 채우고 'filled-primary' 클래스 추가
            if (classInfo) {
                cell.classList.add('filled-primary');
                cell.innerHTML = `
                    <div class="time-slot" data-day="${day}" data-period="${period}" data-class-id="${classInfo.class_id}" data-grade="${selectedFilterGrade}">
                        <span class="class-name-in-cell">${classInfo.class_name || ''}</span>
                        <span class="class-detail-in-cell">${classInfo.teacher_name || ''}</span>
                        <span class="class-credit-in-cell">${classInfo.credit || 0}単位</span>
                        <span class="term-display-in-cell">${classInfo.term === 1 ? '前期' : '後期'}</span>
                        <button class="remove-button">X</button>
                    </div>
                `;
            } else {
                // 수업이 등록되어 있지 않으면 비어있는 time-slot div만 생성
                // 이 비어있는 time-slot div도 클릭 가능하도록 data 속성 부여
                cell.innerHTML = `
                    <div class="time-slot" data-day="${day}" data-period="${period}" data-class-id="" data-grade="${selectedFilterGrade}"></div>
                `;
            }
            row.appendChild(cell);
        });
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
                // 이미 수업이 있는 칸이라면, 현재 선택된 수업으로 교체
                // 또는 선택된 수업이 없는 칸에 추가
                if (isLoggedIn) {
                    addOrUpdateTimetableEntry(selectedClass.id, day, period, currentGrade);
                } else {
                    alert('ログインして時間割を編集してください。');
                }
            } else if (currentClassIdInSlot) {
                // 선택된 수업은 없지만, 칸에 수업이 이미 있다면 해당 수업 정보를 선택된 수업으로 표시
                // (선택된 수업 정보는 없지만, 시간표에서 기존 수업 정보를 볼 수 있도록)
                // 실제 선택된 수업으로 등록하려면 allClasses에서 해당 classId를 찾아야 합니다.
                const existingClass = allClasses.find(cls => cls.id.toString() === currentClassIdInSlot);
                if (existingClass) {
                    selectedClass = {
                        id: existingClass.id,
                        name: existingClass.name,
                        credit: existingClass.credit,
                        term: existingClass.term,
                        grade: existingClass.grade,
                        teacher_name: existingClass.teacher_name,
                        room_number: existingClass.room_number
                    };
                    currentSelectedClassNameSpan.textContent = selectedClass.name;
                    currentSelectedClassCreditSpan.textContent = selectedClass.credit;
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
            addOrUpdateTimetableEntry(selectedClass.id, day, period, currentGrade);
        } else {
            alert('ログインして時間割を編集してください。');
        }
    } else {
        alert('授業一覧から追加する授業を選択してください。');
    }
});


// --- 시간표 항목 추가 또는 업데이트 함수 ---
function addOrUpdateTimetableEntry(classId, day, period, grade) {
    // 이미 해당 칸에 수업이 있는지 확인
    const key = `${currentUserId}-${grade}-${day}-${period}`;
    const existingEntry = userTimetableMap.get(key);

    // 새롭게 추가할 classItem 정보 찾기
    const classToAdd = allClasses.find(cls => cls.id.toString() === classId.toString());
    if (!classToAdd) {
        alert('選択された授業情報が見つかりません。');
        return;
    }

    // 맵 업데이트
    userTimetableMap.set(key, {
        user_id: currentUserId,
        timetable_grade: grade, // user_timetables의 grade 컬럼으로 사용
        day: day,
        period: period,
        class_id: classId,
        class_name: classToAdd.name,
        credit: classToAdd.credit,
        term: classToAdd.term,
        teacher_name: classToAdd.teacher_name, // classes 테이블에 teacher_name, room_number 컬럼이 있어야 함
        room_number: classToAdd.room_number
    });

    renderTimetable(); // 시간표 다시 그리기
    selectedClass = null; // 선택된 수업 초기화
    selectedClassInfoDiv.style.display = 'none'; // 선택 정보 숨기기
}

// --- 시간표 항목 제거 함수 ---
function removeTimetableEntry(day, period, grade) {
    const key = `${currentUserId}-${grade}-${day}-${period}`;
    userTimetableMap.delete(key); // 맵에서 삭제

    renderTimetable(); // 시간표 다시 그리기
    selectedClass = null; // 선택된 수업 초기화
    selectedClassInfoDiv.style.display = 'none'; // 선택 정보 숨기기
}

// --- 총 학점 업데이트 함수 ---
function updateTotalCredits() {
    let total = 0;
    const selectedFilterGrade = parseInt(gradeSelectFilter.value); // 현재 선택된 필터 학년

    userTimetableMap.forEach(item => {
        // 현재 필터링된 학년의 수업만 학점 계산에 포함
        if (item.timetable_grade === selectedFilterGrade) {
             total += item.credit;
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

    // Map에서 현재 학년의 시간표 데이터만 추출하여 배열로 변환
    const timetableDataToSave = [];
    userTimetableMap.forEach(item => {
        if (item.user_id === currentUserId && item.timetable_grade === currentGradeToSave) {
            timetableDataToSave.push({
                class_id: item.class_id,
                day: item.day,
                period: item.period,
                grade: item.timetable_grade // user_timetables에 저장될 grade
            });
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
            // 저장 성공 후, 맵 데이터를 서버와 동기화 (필요하다면)
            // 간단하게는 페이지 새로고침하여 서버에서 최신 데이터 다시 가져오기
            // 또는 userTimetableMap을 현재 저장된 내용으로 업데이트
            location.reload(); // 새로고침하여 최신 데이터 반영
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
searchClassBtn.addEventListener('click', renderClassesTable);
termSelect.addEventListener('change', renderClassesTable);
creditSelect.addEventListener('change', renderClassesTable);
classSearchInput.addEventListener('input', renderClassesTable); // 실시간 검색
gradeSelectFilter.addEventListener('change', () => {
    renderClassesTable(); // 수업 목록 필터링
    renderTimetable();    // 시간표 다시 그리기 (학년 변경 시)
});
applyFilterBtn.addEventListener('click', () => {
    renderClassesTable(); // 수업 목록 필터링
    renderTimetable();    // 시간표 다시 그리기 (학년 변경 시)
});


// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', initializePage);