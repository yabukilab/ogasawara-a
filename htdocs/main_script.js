document.addEventListener('DOMContentLoaded', function() {
    // DOM 요소 선택
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const facultySelect = document.getElementById('facultyFilter'); // 이 셀렉트는 현재 사용하지 않거나, category1/2/3 중 하나로 대체해야 합니다.
    const classListContainer = document.getElementById('class-list');
    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 시간표 저장 로직
    let currentUserId = null;
    if (typeof window.currentUserIdFromPHP !== 'undefined' && window.currentUserIdFromPHP !== null) {
        currentUserId = window.currentUserIdFromPHP;
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲーストモードで動作します。");
    }

    let draggedClass = null;

    // --- 1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value;
        const faculty = facultySelect ? facultySelect.value : ''; // facultySelect가 존재하면 값 가져옴

        // show_lessons.php로부터 수업 데이터를 가져옵니다.
        // 현재 show_lessons.php는 category1, category2, category3를 반환하므로,
        // faculty 필터를 category1 등으로 매핑하거나, 여기서 사용하지 않아야 합니다.
        // 여기서는 일단 show_lessons.php가 term과 faculty 필터를 받을 수 있다고 가정하고 그대로 둡니다.
        // (단, 실제 DB 컬럼명이 다르므로 show_lessons.php에서 category1 등과 연결 필요)
        fetch(`show_lessons.php?grade=${grade}&term=${term}&faculty=${faculty}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(classes => {
                classListContainer.innerHTML = '';
                if (classes.length === 0) {
                    classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                    return;
                }
                classes.forEach(cls => {
                    const classItem = document.createElement('div');
                    classItem.classList.add('class-item');
                    classItem.setAttribute('draggable', true);
                    classItem.dataset.id = cls.id;
                    classItem.dataset.name = cls.name;
                    classItem.dataset.credit = cls.credit;
                    
                    // --- 변경 사항 시작: 데이터셋 이름과 값 매핑 ---
                    // 'term' 컬럼이 DB에 'term'으로 있다면 그대로 사용, 없다면 다른 category 등으로 매핑 필요.
                    // 현재 `class` 테이블 구조에는 `term` 대신 `category1`, `category2`, `category3` 등이 있습니다.
                    // 어떤 컬럼이 학기 정보를 담고 있는지 명확하지 않으므로, 임시로 `category1`을 사용하거나,
                    // 실제 학기 정보를 담고 있는 컬럼명(예: `semester` 등)으로 바꿔야 합니다.
                    // 지금은 `term` 필터는 있지만, `cls.term`은 `show_lessons.php`가 반환하지 않는다고 가정하고
                    // `category1`을 예시로 표시해봅니다.
                    classItem.dataset.grade = cls.grade; // DB 컬럼명과 일치
                    classItem.dataset.category1 = cls.category1; // category1 추가
                    classItem.dataset.category2 = cls.category2; // category2 추가
                    classItem.dataset.category3 = cls.category3; // category3 추가
                    // --- 변경 사항 끝 ---

                    // 수업 항목 표시 내용 변경:
                    // `term`과 `faculty` 대신 실제 `class` 테이블 컬럼을 사용합니다.
                    // 학기 정보로 `category1`을 사용한다고 가정하고, `faculty`는 `category2`로 표시해봅니다.
                    classItem.innerHTML = `
                        <strong>${cls.name}</strong> (${cls.credit}単位)<br>
                        <span class="class-info-small">${cls.grade}年 / ${cls.category1} / ${cls.category2}</span>
                    `;
                    classListContainer.appendChild(classItem);
                });
                addDragListeners();
            })
            .catch(error => {
                console.error('授業データの取得に失敗しました:', error);
                classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。</p>';
            });
    }

    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchAndDisplayClasses();
        });
    }

    fetchAndDisplayClasses();

    // --- 2. 드래ッグ 앤 ドロップ機能 (로직 변경 없음) ---
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
                const classGrade = draggedClass.dataset.grade; // 'grade'는 일치
                
                // --- 변경 사항 시작: category1, category2를 가져오도록 변경 ---
                const classCategory1 = draggedClass.dataset.category1;
                const classCategory2 = draggedClass.dataset.category2;
                // --- 변경 사항 끝 ---

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

    // --- 3. 시간표에서 수업 삭제 (로직 변경 없음) ---
    function removeClassFromTimetable(event) {
        const cell = event.target.closest('.time-slot');
        if (cell) {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        }
    }

    // --- 4. 시간표 저장 기능 (로직 변경 없음 - 서버로 보내는 데이터 구조는 동일) ---
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

    // --- 5. 저장된 시간표 불러오기 (로직 변경 - 표시되는 정보 반영) ---
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
                    timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                        cell.innerHTML = '';
                        cell.classList.remove('filled-primary');
                    });

                    data.timetable.forEach(entry => {
                        const cellSelector = `.time-slot[data-day="${entry.day_of_week}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(cellSelector);

                        if (targetCell) {
                            // get_timetable.php에서 수업 정보 (name, credit, grade, category1, category2)를 함께 반환한다고 가정
                            const className = entry.class_name || '不明な授業';
                            const classCredit = entry.class_credit || '?';
                            const classGrade = entry.grade || '';
                            const classCategory1 = entry.category1 || ''; // category1 추가
                            const classCategory2 = entry.category2 || ''; // category2 추가

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