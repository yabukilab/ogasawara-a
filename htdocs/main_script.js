document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. 전역 (이 스크립트 파일 내) 변수 초기화 및 로그인 사용자 ID 설정
    let currentUserId = null;
    const bodyElement = document.body;
    const userIdFromDataAttribute = bodyElement.dataset.userId; // data-user-id 속성 값 가져오기

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10); // 숫자로 변환
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。");
    }

    console.log("DEBUG: main_script.js - currentUserIdの最終値:", currentUserId, "タイプ:", typeof currentUserId);

    // =========================================================
    // 2. DOM 요소 선택
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const classListContainer = document.getElementById('lesson-list-container');
    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    const timetableTermSelect = document.getElementById('timetableTermSelect');
    let draggedClass = null; // 드래그 중인 수업 데이터를 저장할 변수
    let totalCredit = 0;
    const currentTotalCreditSpan = document.getElementById('current-total-credit');

    // =========================================================
    // 3. 함수 정의
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
        if (timetableTable) { // timetableTableがnullでない場合に実行
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

                    // セルに既に授業がある場合のチェック
                    if (this.querySelector('.class-item-in-cell')) {
                        alert('この時間枠にはすでに授業があります。');
                        return;
                    }

                    const classId = draggedClass.dataset.id;
                    const className = draggedClass.dataset.name;
                    const classCredit = parseInt(draggedClass.dataset.credit, 10);

                    // 新しいクラスアイテムを作成
                    const classItemInCell = document.createElement('div');
                    classItemInCell.classList.add('class-item-in-cell');
                    classItemInCell.dataset.classId = classId;
                    classItemInCell.dataset.day = this.dataset.day;
                    classItemInCell.dataset.period = this.dataset.period;

                    classItemInCell.innerHTML = `
                        <span class="class-name-in-cell">${className}</span>
                        <span class="class-credit-in-cell">${classCredit}単位</span>
                        <button class="remove-button">&times;</button>
                    `;
                    this.appendChild(classItemInCell);
                    this.classList.add('filled-primary');

                    // 削除ボタンのリスナー追加
                    classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                    // 学点の更新
                    totalCredit += classCredit;
                    updateAndDisplayTotalCredit();
                });
            });
        }
    }

    // 時間割の保存機能
    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。ログイン後に時間割を保存できます。');
            window.location.href = 'login.php';
            return;
        }

        const selectedTimetableGrade = timetableGradeSelect.value;
        const selectedTimetableTerm = timetableTermSelect.value;

        const timetableData = [];
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
            const classId = itemInCell.dataset.classId;
            const day = itemInCell.dataset.day;
            const period = itemInCell.dataset.period;

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
                timetable_term: selectedTimetableTerm,
                timetable: timetableData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert('時間割が正常に保存されました！');
                loadTimetable();
            } else {
                alert('時間割の保存に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('時間割の保存中にエラーが発生しました:', error);
            alert('時間割の保存中にエラーが発生しました。ネットワーク接続を確認してください。');
        });
    }

    // 時間割の読み込みfunction loadTimetable() {
    if (currentUserId === null) {
        console.log("ユーザーがログインしていません。");
        return;
    }

    const targetGrade = timetableGradeSelect.value;
    const targetTerm = timetableTermSelect.value;

    if (!targetGrade || !targetTerm) {
        console.warn("時間割のロードに失敗: 学年と学期を選択してください");
        return;
    }

    // --- 修正: timetableTable が null かどうか確認 ---
    if (!timetableTable) {
        console.error("時間割テーブルが見つかりません。DOMの読み込みに失敗しました。");
        return; // timetableTable が null の場合は処理を終了
    }
    // --- 修正ここまで ---

    totalCredit = 0;
    updateAndDisplayTotalCredit();

    timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
        itemInCell.remove();
    });
    timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
        cell.classList.remove('filled-primary');
    });

    fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                data.timetable.forEach(entry => {
                    const cellSelector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                    const targetCell = timetableTable.querySelector(cellSelector);
                    if (targetCell) {
                        const classItemInCell = document.createElement('div');
                        classItemInCell.classList.add('class-item-in-cell');
                        classItemInCell.dataset.classId = entry.class_id;
                        classItemInCell.dataset.day = entry.day;
                        classItemInCell.dataset.period = entry.period;
                        classItemInCell.innerHTML = `
                            <span class="class-name-in-cell">${entry.class_name}</span>
                            <span class="class-credit-in-cell">${entry.class_credit}単位</span>
                            <span class="category-display-in-cell">${entry.class_original_grade}年</span>
                            <button class="remove-button">&times;</button>
                        `;
                        targetCell.appendChild(classItemInCell);
                        targetCell.classList.add('filled-primary');
                        classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
                        totalCredit += entry.class_credit;
                        updateAndDisplayTotalCredit();
                    }
                });
            }
        })
        .catch(error => console.error('時間割のロード中にエラーが発生しました:', error));
}


    // 総学点の更新と表示
    function updateAndDisplayTotalCredit() {
        if (currentTotalCreditSpan) {
            currentTotalCreditSpan.textContent = totalCredit;
        }
    }

    // =========================================================
    // 4. イベントリスナー登録と初期実行
    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchAndDisplayClasses();
        });
    }

    fetchAndDisplayClasses();
    addDropListeners();

    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    }

    if (timetableGradeSelect) {
        timetableGradeSelect.addEventListener('change', loadTimetable);
    }

    if (timetableTermSelect) {
        timetableTermSelect.addEventListener('change', loadTimetable);
    }

    if (currentUserId !== null) {
        loadTimetable();
    } else {
        console.log("ユーザーがログインしていません。時間割の自動ロードは行われません。");
    }

    updateAndDisplayTotalCredit();
});
