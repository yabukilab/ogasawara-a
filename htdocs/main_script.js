document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. 전역 (이 스크립트 파일 내) 변수 초기화 및 로그인 사용자 ID 설정
    //    body 태그의 data-user-id 속성에서 사용자 ID를 읽어옵니다.
    // =========================================================
    let currentUserId = null;
    const bodyElement = document.body; // body 요소 참조
    const userIdFromDataAttribute = bodyElement.dataset.userId; // data-user-id 속성 값 가져오기

    // userIdFromDataAttribute는 문자열 "null" 또는 실제 ID 숫자 문자열 ("5")이 됩니다.
    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10); // 숫자로 변환
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }

    console.log("DEBUG: main_script.js - currentUserIdの最終値:", currentUserId, "タイプ:", typeof currentUserId);
    // =========================================================

    // =========================================================
    // 2. DOM 요소 선택
    //    모든 DOM 요소 선택은 이 'DOMContentLoaded' 블록 안에서 이루어져야 합니다.
    // =========================================================
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');

    const classListContainer = document.getElementById('lesson-list-container');

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 새로 추가된 학년 선택 드롭다운 (index.php에서 추가됨)
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    // 새로 추가된 학기 선택 드롭다운 (index.php에서 추가됨)
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    let draggedClass = null; // 드래그 중인 수업 데이터를 저장할 변수

    // =========================================================
    // 3. 함수 정의
    // =========================================================

    // --- 3.1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value;

        fetch(`show_lessons.php?grade=${grade}&term=${term}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
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
                        classItem.dataset.grade = cls.grade;
                        classItem.dataset.category1 = cls.category1;
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3;

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
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('授業データの取得中にネットワークエラーが発生しました:', error);
                classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
            });
    }

    // --- 3.2. ドラッグ開始時イベントリスナー追加 ---
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

    // --- 3.3. ドロップゾーン (時間割セル) イベントリスナー追加 ---
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
                    <span class="category-display-in-cell">${classGrade}年</span> <button class="remove-button">&times;</button>
                `;
                this.classList.add('filled-primary');
                this.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
            });
        });
    }

    addDropListeners();

    // --- 3.4. 時間割から授業削除 ---
    function removeClassFromTimetable(event) {
        const cell = event.target.closest('.time-slot');
        if (cell) {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        }
    }

    // --- 3.5. 時間割保存機能 (timetable_grade, timetable_term 추가) ---
    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。ログイン後に時間割を保存できます。');
            window.location.href = 'login.php';
            return;
        }

        const selectedTimetableGrade = timetableGradeSelect.value; // 현재 선택된 학년 가져오기
        const selectedTimetableTerm = timetableTermSelect.value;   // 새로 추가된 학기 가져오기

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
                timetable_grade: selectedTimetableGrade,
                timetable_term: selectedTimetableTerm, // <-- 학기 정보 추가
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
    }

    // --- 3.6. 保存された時間割読み込み (timetable_grade, timetable_term 추가) ---
    function loadTimetable() { // 파라미터 제거, 내부에서 직접 현재 선택된 값 사용
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        const targetGrade = timetableGradeSelect.value;
        const targetTerm = timetableTermSelect.value; // 현재 선택된 학기 사용

        // 기존 시간표 초기화
        timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        });

        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`) // <-- 학년 및 학기 정보 추가
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    data.timetable.forEach(entry => {
                        const cellSelector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(cellSelector);

                        if (targetCell) {
                            const className = entry.class_name || '不明な授業';
                            const classCredit = entry.class_credit || '?';
                            const classOriginalGrade = entry.class_original_grade || ''; // 수업의 실제 학년

                            targetCell.innerHTML = `
                                <span class="class-name-in-cell" data-class-id="${entry.class_id}">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classOriginalGrade}年</span>
                                <button class="remove-button">&times;</button>
                            `;
                            targetCell.classList.add('filled-primary');
                            targetCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
                        } else {
                            console.warn(`時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period}`);
                        }
                    });
                    console.log(`時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。`);
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
            });
    }

    // =========================================================
    // 4. イベントリスナー登録と初期実行
    // =========================================================

    // フィルターフォーム送信イベント
    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchAndDisplayClasses();
        });
    }

    // 페이지 로드 시 수업 목록을 한 번 로드합니다.
    fetchAndDisplayClasses();

    // 드롭 리스너는 페이지 로드 시 한 번만 추가하면 됩니다. (셀은 고정되어 있으므로)
    addDropListeners();

    // 시간표 저장 버튼 클릭 이벤트
    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    }

    // 학년 또는 학기 선택 변경 시 이벤트 리스너
    if (timetableGradeSelect) {
        timetableGradeSelect.addEventListener('change', loadTimetable); // 학년 변경 시 시간표 로드
    }
    if (timetableTermSelect) {
        timetableTermSelect.addEventListener('change', loadTimetable); // 학기 변경 시 시간표 로드
    }

    // 로그인된 사용자일 경우, 저장된 시간표를 자동으로 로드합니다.
    // 페이지 로드 시 초기 학년/학기 (기본적으로 1학년/전기) 시간표를 로드
    if (currentUserId !== null) {
        loadTimetable(); // 이제 loadTimetable은 인수를 받지 않고 내부에서 직접 현재 선택된 값을 가져옵니다.
    } else {
        console.log("ユーザーがログインしていません。時間割の自動ロードは行われません。");
    }
}); // DOMContentLoaded 閉じ括弧