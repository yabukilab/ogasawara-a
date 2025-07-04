document.addEventListener('DOMContentLoaded', function() {
    // DOM 요소 선택
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const facultySelect = document.getElementById('facultyFilter'); 

    // 수업 목록 컨테이너 ID를 'lesson-list-container'로 변경
    const classListContainer = document.getElementById('lesson-list-container'); 

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 시간표 저장 로직
    let currentUserId = null;
    // window.currentUserIdFromPHP는 index.php에서 설정됩니다.
    if (typeof window.currentUserIdFromPHP !== 'undefined' && window.currentUserIdFromPHP !== null) {
        currentUserId = window.currentUserIdFromPHP;
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。");
    }

    let draggedClass = null;

    // --- 1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value;
        const faculty = facultySelect ? facultySelect.value : ''; 

        fetch(`show_lessons.php?grade=${grade}&term=${term}&faculty=${faculty}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => { 
                classListContainer.innerHTML = ''; // 기존 수업 목록을 비웁니다.
                
                if (data.status === 'success') {
                    const classes = data.lessons; // 실제 수업 데이터는 data.lessons 안에 있습니다.

                    if (classes.length === 0) {
                        classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                        return;
                    }

                    classes.forEach(cls => {
                        const classItem = document.createElement('div');
                        classItem.classList.add('class-item', 'draggable'); 
                        classItem.setAttribute('draggable', true);
                        
                        // 데이터셋 속성 할당: JSON 응답의 키와 정확히 일치시킵니다.
                        classItem.dataset.id = cls.id;
                        classItem.dataset.name = cls.name;
                        classItem.dataset.credit = cls.credit;
                        classItem.dataset.grade = cls.grade;
                        classItem.dataset.category1 = cls.category1;
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3;

                        // 수업 항목 표시 내용 변경
                        classItem.innerHTML = `
                            <div class="lesson-name">${cls.name}</div>
                            <div class="lesson-details">
                                <span class="lesson-credit">${cls.credit}単位</span>
                                <span class="lesson-category">${cls.category1} (${cls.grade}年)</span>
                            </div>
                        `;
                        classListContainer.appendChild(classItem);
                    });
                    addDragListeners(); // 드래그 리스너는 수업 항목 추가 후 호출
                } else {
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('授業データの取得中にネットワークエラーが発生しました:', error);
                classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
            });
    }

    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault(); // フォームのデフォルト送信を防止
            fetchAndDisplayClasses();
        });
        // 필터 변경 시 자동으로 수업 목록을 업데이트하고 싶다면 아래 주석을 해제하세요.
        // gradeSelect.addEventListener('change', fetchAndDisplayClasses);
        // termSelect.addEventListener('change', fetchAndDisplayClasses);
        // facultySelect.addEventListener('change', fetchAndDisplayClasses);
    }
    
    // 페이지 로드 시 수업 목록을 한 번 불러옵니다.
    fetchAndDisplayClasses();

    // --- 2. 드래ッグ 앤 ドロップ機能 ---
    function addDragListeners() {
        const classItems = document.querySelectorAll('.class-item');
        classItems.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                draggedClass = this;
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', this.dataset.id);
                this.classList.add('dragging');
            });
            item.addEventListener('dragend', function() {
                this.classList.remove('dragging');
            });
        });
    }

    function addDropListeners() {
        const timeSlots = timetableTable.querySelectorAll('.time-slot');
        timeSlots.forEach(slot => {
            slot.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                this.classList.add('drag-over');
            });
            slot.addEventListener('dragleave', function() {
                this.classList.remove('drag-over');
            });
            slot.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (!draggedClass) return;

                const classId = draggedClass.dataset.id;
                const className = draggedClass.dataset.name;
                const classCredit = draggedClass.dataset.credit;
                const classGrade = draggedClass.dataset.grade;
                const classCategory1 = draggedClass.dataset.category1;
                const classCategory2 = draggedClass.dataset.category2;
                
                if (this.classList.contains('filled-primary')) {
                    alert('この時間枠にはすでに授業があります。');
                    return;
                }

                this.innerHTML = `
                    <span class="class-name-in-cell" data-class-id="${classId}">${className}</span>
                    <span class="class-credit-in-cell">${classCredit}単位</span>
                    <span class="category-display-in-cell">${classGrade}年 / ${classCategory1} / ${classCategory2}</span>
                    <button class="remove-button">&times;</button>
                `;
                this.classList.add('filled-primary');
                this.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
            });
        });
    }

    addDropListeners();

    // --- 3. 시간표에서 수업 삭제 ---
    function removeClassFromTimetable(event) {
        const cell = event.target.closest('.time-slot');
        if (cell) {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        }
    }

    // --- 4. 시간표 저장 기능 ---
    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', function() {
            if (currentUserId === null) {
                alert('ログインしていません。ログイン後に時間割を保存できます。');
                window.location.href = 'login.php';
                return;
            }

            const timetableData = [];
            timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                const classId = cell.querySelector('.class-name-in-cell').dataset.classId;
                // data-day 속성이 이제 영어 요일로 설정되어 있습니다.
                const day = cell.dataset.day; 
                const period = cell.dataset.period;

                timetableData.push({
                    class_id: classId,
                    day_of_week: day,
                    period: period
                });
            });

            fetch('save_timetable.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: currentUserId,
                    timetable: timetableData
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    alert('時間割が正常に保存されました！');
                } else {
                    alert('時間割の保存に失敗しました: ' + data.message);
                }
            })
            .catch(error => {
                console.error('時間割の保存中にエラーが発生しました:', error);
                alert('時間割の保存中にエラーが発生しました。ネットワーク接続を確認してください。');
            });
        });
    }

    // --- 5. 저장된 시간표 불러오기 ---
    function loadTimetable() {
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        fetch(`get_timetable.php?user_id=${currentUserId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // 기존 시간표 초기화
                    timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                        cell.innerHTML = '';
                        cell.classList.remove('filled-primary');
                    });

                    data.timetable.forEach(entry => {
                        // 셀 선택 시 data-day 속성이 영어 요일로 변경되었으므로 셀렉터도 이에 맞춰 수정
                        const cellSelector = `.time-slot[data-day="${entry.day_of_week}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(cellSelector);

                        if (targetCell) {
                            // get_timetable.php에서 수업 정보 (name, credit, grade, category1, category2)를 함께 반환한다고 가정
                            const className = entry.class_name || '不明な授業';
                            const classCredit = entry.class_credit || '?';
                            const classGrade = entry.grade || '';
                            const classCategory1 = entry.category1 || ''; 
                            const classCategory2 = entry.category2 || ''; 

                            targetCell.innerHTML = `
                                <span class="class-name-in-cell" data-class-id="${entry.class_id}">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classGrade}年 / ${classCategory1} / ${classCategory2}</span>
                                <button class="remove-button">&times;</button>
                            `;
                            targetCell.classList.add('filled-primary');
                            targetCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
                        }
                    });
                    console.log('時間割が正常にロードされました。');
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
            });
    }

    if (currentUserId !== null) {
        loadTimetable();
    } else {
        console.log("ユーザーがログインしていません。時間割の自動ロードは行われません。");
    }
});