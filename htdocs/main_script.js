document.addEventListener('DOMContentLoaded', function() {
    // DOM 요소 선택
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    // termSelect와 facultySelect는 현재 show_lessons.php가 term/faculty 컬럼을 직접 받지 않으므로
    // category1 등을 필터링하는 용도로 사용하려면 show_lessons.php의 SQL 쿼리를 수정해야 합니다.
    // 여기서는 일단 기존대로 두지만, 필터링이 작동하지 않으면 이 부분을 고려해야 합니다.
    const termSelect = document.getElementById('termFilter'); 
    const facultySelect = document.getElementById('facultyFilter'); 

    // 수업 목록이 표시될 컨테이너의 ID를 'class-list'에서 'lesson-list-container'로 변경합니다.
    // 이전에 `index.php`에서 `id="lesson-list-container"`를 사용하도록 제안했었기 때문입니다.
    const classListContainer = document.getElementById('lesson-list-container'); 

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 시간표 저장 로직
    let currentUserId = null;
    if (typeof window.currentUserIdFromPHP !== 'undefined' && window.currentUserIdFromPHP !== null) {
        currentUserId = window.currentUserIdFromPHP;
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。");
    }

    let draggedClass = null;

    // --- 1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value; // 현재 show_lessons.php는 'term' 필터를 직접 받지 않습니다.
        const faculty = facultySelect ? facultySelect.value : ''; // 현재 show_lessons.php는 'faculty' 필터를 직접 받지 않습니다.

        // show_lessons.php로부터 수업 데이터를 가져옵니다.
        // show_lessons.php가 grade 필터만 처리하고 있다고 가정합니다.
        // term과 faculty 필터를 적용하려면 show_lessons.php를 추가 수정해야 합니다.
        fetch(`show_lessons.php?grade=${grade}&term=${term}&faculty=${faculty}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => { // 응답 객체 이름을 'data'로 변경하여 status, lessons를 바로 접근
                classListContainer.innerHTML = ''; // 기존 수업 목록을 비웁니다.
                
                // 서버 응답의 status를 확인합니다.
                if (data.status === 'success') {
                    const classes = data.lessons; // 실제 수업 데이터는 data.lessons 안에 있습니다.

                    if (classes.length === 0) {
                        classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                        return;
                    }

                    classes.forEach(cls => {
                        const classItem = document.createElement('div');
                        classItem.classList.add('class-item', 'draggable'); // 'draggable' 클래스 추가
                        classItem.setAttribute('draggable', true);
                        
                        // 데이터셋 속성 할당: JSON 응답의 키와 정확히 일치시켜야 합니다.
                        classItem.dataset.id = cls.id;
                        classItem.dataset.name = cls.name;
                        classItem.dataset.credit = cls.credit;
                        classItem.dataset.grade = cls.grade;
                        classItem.dataset.category1 = cls.category1;
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3;

                        // 수업 항목 표시 내용 변경:
                        // JSON 응답의 키 (name, credit, grade, category1, category2)를 사용합니다.
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
                    // status가 'error'인 경우 메시지 표시
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
        // ページロード時にもフィルターを適用하여 수업을 불러오도록 추가
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
                // classCategory3도 필요하다면 여기서 가져와 사용할 수 있습니다.

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