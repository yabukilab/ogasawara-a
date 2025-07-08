document.addEventListener('DOMContentLoaded', function () {
    let currentUserId = null;
    const bodyElement = document.body;
    const userIdFromDataAttribute = bodyElement.dataset.userId;

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10);
    } else {
        console.warn("警告: currentUserIdが定義されていません。ゲストモードで動作します。");
    }

    // --- 여기부터 추가된 console.log 부분입니다 ---
    console.log("main_script.js - userId from data attribute:", userIdFromDataAttribute);
    console.log("main_script.js - parsed currentUserId:", currentUserId);
    // --- 추가된 console.log 부분 끝 ---

    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const classListContainer = document.getElementById('lesson-list-container');
    const timetableTable = document.getElementById('timetable-table'); // 이 요소가 HTML에 있는지 확인 필요
    const saveTimetableButton = document.getElementById('saveTimetableBtn'); // 이 요소가 HTML에 있는지 확인 필요
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    let draggedClass = null;
    let totalCredit = 0;
    const countedClassIds = new Set(); // 重複防止用セット
    const currentTotalCreditSpan = document.getElementById('current-total-credit');

    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value;

        fetch(`show_lessons.php?grade=${grade}&term=${term}`)
            .then(response => response.json())
            .then(data => {
                classListContainer.innerHTML = '';
                if (data.status === 'success') {
                    const classes = data.lessons;
                    if (classes.length === 0) {
                        classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                        return;
                    }
                    classes.forEach(cls => {
                        const classItem = document.createElement('div');
                        classItem.classList.add('class-item', 'draggable');
                        classItem.setAttribute('draggable', true);

                        classItem.dataset.id = cls.id;
                        classItem.dataset.name = cls.name;
                        classItem.dataset.credit = cls.credit;
                        classItem.dataset.grade = cls.grade; // grade -> class_original_grade로 변경해야 할 수도 있습니다. 데이터베이스 필드명 확인 필요.

                        classItem.innerHTML = `
                            <div class="lesson-name">${cls.name}</div>
                            <div class="lesson-details">
                                <span class="lesson-credit">${cls.credit}単位</span>
                            </div>
                        `;
                        classListContainer.appendChild(classItem);
                    });
                    addDragListeners();
                } else {
                    classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                }
            })
            .catch(() => {
                classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。</p>';
            });
    }

    function addDragListeners() {
        const classItems = document.querySelectorAll('.class-item');
        classItems.forEach(item => {
            item.addEventListener('dragstart', function (e) {
                draggedClass = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.id);
                this.classList.add('dragging');
            });
            item.addEventListener('dragend', function () {
                this.classList.remove('dragging');
            });
        });
    }

    function addDropListeners() {
        const timeSlots = timetableTable.querySelectorAll('.time-slot');
        timeSlots.forEach(slot => {
            slot.addEventListener('dragover', function (e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });

            slot.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });

            slot.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                if (!draggedClass) return;

                // 드래그앤드롭 시 중복 방지 (기존 로직)
                if (this.querySelector('.class-item-in-cell')) {
                    alert('この時間枠にはすでに授業があります。');
                    return;
                }

                // 아래 `createClassItemInCellElement` 함수를 재활용합니다.
                const classData = {
                    class_id: draggedClass.dataset.id,
                    class_name: draggedClass.dataset.name,
                    class_credit: parseInt(draggedClass.dataset.credit, 10),
                    class_original_grade: draggedClass.dataset.grade, // classGrade 사용
                    day: this.dataset.day,
                    period: this.dataset.period
                };
                
                const classItemInCell = createClassItemInCellElement(classData);
                this.appendChild(classItemInCell);
                this.classList.add('filled-primary');

                // 학점 계산 로직은 그대로 유지
                if (!countedClassIds.has(classData.class_id)) {
                    totalCredit += classData.class_credit;
                    countedClassIds.add(classData.class_id);
                }
                updateAndDisplayTotalCredit();
            });
        });
    }

    // 수업 아이템 DOM 요소를 생성하는 헬퍼 함수
    function createClassItemInCellElement(entry) {
        const classItemInCell = document.createElement('div');
        classItemInCell.classList.add('class-item-in-cell');
        classItemInCell.setAttribute('draggable', true); // 셀 안에서도 드래그 가능하게
        classItemInCell.dataset.classId = entry.class_id;
        classItemInCell.dataset.day = entry.day;
        classItemInCell.dataset.period = entry.period;

        const className = entry.class_name || '不明な授業';
        const classCredit = parseInt(entry.class_credit, 10) || 0;
        const classOriginalGrade = entry.class_original_grade || ''; // 데이터베이스 필드명 확인 필요

        classItemInCell.innerHTML = `
            <span class="class-name-in-cell">${className}</span>
            <span class="class-credit-in-cell">${classCredit}単位</span>
            <span class="category-display-in-cell">${classOriginalGrade}年</span>
            <button class="remove-button">&times;</button>
        `;
        classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
        return classItemInCell;
    }


    function removeClassFromTimetable(event) {
        const classItemInCell = event.target.closest('.class-item-in-cell');
        const cell = event.target.closest('.time-slot');

        if (classItemInCell && cell) {
            const removedCreditSpan = classItemInCell.querySelector('.class-credit-in-cell');
            const classId = classItemInCell.dataset.classId;
            const removedCredit = parseInt(removedCreditSpan.textContent.replace('単位', ''), 10);

            // 중요: 이 셀에 있던 수업 아이템이 제거되는 것이므로,
            // 다른 셀에 동일한 classId를 가진 수업이 있을 수 있더라도,
            // 이 셀에서는 해당 수업 아이템을 제거하는 것이 맞습니다.
            // 총 학점 계산 로직은 'countedClassIds'를 사용하여
            // 전체 시간표에서 해당 classId가 더 이상 존재하지 않을 때만 학점을 감산해야 합니다.
            classItemInCell.remove(); // 이 줄이 먼저 와야 정확히 남은 요소 수를 셀 수 있습니다.

            const remainingInstances = document.querySelectorAll(`.class-item-in-cell[data-class-id="${classId}"]`);
            if (remainingInstances.length === 0) { // 해당 classId를 가진 수업이 시간표에 하나도 남아있지 않다면
                totalCredit -= removedCredit;
                countedClassIds.delete(classId);
            }
            updateAndDisplayTotalCredit();
            
            // 셀에 더 이상 수업이 없으면 filled-primary 클래스 제거
            if (!cell.querySelector('.class-item-in-cell')) {
                cell.classList.remove('filled-primary');
            }
        }
    }

    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。');
            window.location.href = 'login.php';
            return;
        }

        const selectedTimetableGrade = timetableGradeSelect.value;
        const selectedTimetableTerm = timetableTermSelect.value;

        const timetableData = [];
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
            timetableData.push({
                class_id: itemInCell.dataset.classId,
                day_of_week: itemInCell.dataset.day,
                period: itemInCell.dataset.period
            });
        });

        fetch('save_timetable.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: currentUserId,
                timetable_grade: selectedTimetableGrade,
                timetable_term: selectedTimetableTerm,
                timetable: timetableData
            })
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    alert('時間割を保存しました');
                    loadTimetable(); // 저장 후 시간표 다시 로드
                } else {
                    alert('時間割の保存に失敗しました: ' + data.message);
                }
            })
            .catch(() => {
                alert('時間割の保存中にエラーが発生しました。');
            });
    }

    function loadTimetable() {
        if (currentUserId === null) return;

        const targetGrade = timetableGradeSelect.value;
        const targetTerm = timetableTermSelect.value;

        totalCredit = 0;
        countedClassIds.clear();
        updateAndDisplayTotalCredit();

        // 모든 기존 수업 아이템 및 filled-primary 클래스 제거
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => itemInCell.remove());
        timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => cell.classList.remove('filled-primary'));

        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // 여기에 각 시간 슬롯에 대한 수업 데이터를 저장할 맵을 추가합니다.
                    // { '月曜日_1': { class_id: '...', class_name: '...' }, ... }
                    const timetableMap = new Map(); 

                    data.timetable.forEach(entry => {
                        const key = `${entry.day}_${entry.period}`;
                        // 이미 해당 시간 슬롯에 수업이 존재하면 추가하지 않습니다.
                        // 이 부분이 중요합니다. 서버에서 한 시간대에 여러 수업을 반환하는 경우를 클라이언트에서 처리합니다.
                        if (timetableMap.has(key)) {
                            console.warn(`警告: ${entry.day} ${entry.period}時間目に複数の授業が割り当てられています。最初の授業のみ表示されます。`);
                            return; // 이미 수업이 있으므로 다음 entry로 넘어갑니다.
                        }
                        timetableMap.set(key, entry); // 첫 번째 수업만 맵에 저장
                    });

                    // 맵에 저장된 각 시간 슬롯의 수업만 순회하여 테이블에 추가합니다.
                    timetableMap.forEach(entry => {
                        const selector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(selector);
                        
                        if (!targetCell) {
                            console.warn(`警告: 時間割セルが見つかりません: ${entry.day} ${entry.period}時間目`);
                            return;
                        }
                        
                        // 셀에 수업 아이템 추가
                        const classItemInCell = createClassItemInCellElement(entry); // 헬퍼 함수 사용
                        targetCell.appendChild(classItemInCell);
                        targetCell.classList.add('filled-primary');

                        // 학점 계산
                        if (!countedClassIds.has(entry.class_id)) {
                            totalCredit += parseInt(entry.class_credit, 10) || 0;
                            countedClassIds.add(entry.class_id);
                        }
                    });
                    updateAndDisplayTotalCredit();
                } else {
                    console.error('時間割の読み込みに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割データの読み込み中にエラーが発生しました。', error);
            });
    }

    function updateAndDisplayTotalCredit() {
        if (currentTotalCreditSpan) {
            currentTotalCreditSpan.textContent = totalCredit;
        }
    }

    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function (e) {
            e.preventDefault();
            fetchAndDisplayClasses();
        });
    }

    fetchAndDisplayClasses(); // 초기 수업 목록 로드
    addDropListeners(); // 드롭 리스너 추가

    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    }

    if (timetableGradeSelect) {
        timetableGradeSelect.addEventListener('change', loadTimetable);
    }

    if (timetableTermSelect) {
        timetableTermSelect.addEventListener('change', loadTimetable);
    }

    // 페이지 로드 시, user_id가 있는 경우 시간표 로드 시도
    if (currentUserId !== null) {
        loadTimetable();
    }

    updateAndDisplayTotalCredit(); // 초기 학점 표시 (아마 0으로 시작)
});