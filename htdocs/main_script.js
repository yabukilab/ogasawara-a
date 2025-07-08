document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. グローバル (このスクリプトファイル内) 変数初期化およびログインユーザーID設定
    //    body タグの data-user-id 属性からユーザーIDを読み込みます。
    // =========================================================
    let currentUserId = null;
    const bodyElement = document.body; // body 要素参照
    const userIdFromDataAttribute = bodyElement.dataset.userId; // data-user-id 属性値取得

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10);
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }

    console.log("DEBUG: main_script.js - currentUserIdの最終値:", currentUserId, "タイプ:", typeof currentUserId);

    // =========================================================
    // 2. DOM 要素選択
    // =========================================================
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilterSelect');
    const termSelect = document.getElementById('termFilterSelect');

    const classListContainer = document.getElementById('lesson-list-container');

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    // ★ 여기서 기본값 강제 설정 추가 ★
    if (timetableGradeSelect && (timetableGradeSelect.value === "" || timetableGradeSelect.value == null)) {
        timetableGradeSelect.value = '1';
        console.warn("timetableGradeSelectの初期値が空だったため、'1'に設定しました。");
    }
    if (timetableTermSelect && (timetableTermSelect.value === "" || timetableTermSelect.value == null)) {
        timetableTermSelect.value = '前期';
        console.warn("timetableTermSelectの初期値が空だったため、'前期'に設定しました。");
    }

    let draggedClass = null;

    let totalCredit = 0;
    const currentTotalCreditSpan = document.getElementById('current-total-credit');

    // =========================================================
    // 3. 関数定義
    // =========================================================

    function fetchAndDisplayClasses() {
        if (!gradeSelect || !termSelect) {
            console.error("エラー: 'gradeFilterSelect' または 'termFilterSelect' の要素が見つかりません。HTMLを確認してください。");
            if (classListContainer) {
                classListContainer.innerHTML = '<p class="message error">授業のフィルターに必要な要素が見つかりません。</p>';
            }
            return;
        }

        const grade = gradeSelect.value;
        const term = termSelect.value;

        fetch(`show_lessons.php?grade=${encodeURIComponent(grade)}&term=${encodeURIComponent(term)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (classListContainer) classListContainer.innerHTML = '';

                if (data.status === 'success') {
                    const classes = data.lessons;

                    if (classes.length === 0) {
                        if (classListContainer) classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
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
                        if (classListContainer) classListContainer.appendChild(classItem);
                    });
                    addDragListeners();
                } else {
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    if (classListContainer) classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('授業データの取得中にネットワークエラーが発生しました:', error);
                if (classListContainer) classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。</p>';
            });
    }

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
        if (!timetableTable) {
            console.warn("警告: 'timetable-table' の要素が見つかりません。ドロップ機能が動作しません。");
            return;
        }
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

                // 기존 수업 모두 제거하여 중복 방지
                this.querySelectorAll('.class-item-in-cell').forEach(el => el.remove());

                const classId = draggedClass.dataset.id;
                const className = draggedClass.dataset.name;
                const classCredit = parseInt(draggedClass.dataset.credit, 10);
                const classGrade = draggedClass.dataset.grade;

                const classItemInCell = document.createElement('div');
                classItemInCell.classList.add('class-item-in-cell');
                classItemInCell.setAttribute('draggable', true);

                classItemInCell.dataset.classId = classId;
                classItemInCell.dataset.day = this.dataset.day;
                classItemInCell.dataset.period = this.dataset.period;

                classItemInCell.innerHTML = `
                    <span class="class-name-in-cell">${className}</span>
                    <span class="class-credit-in-cell">${classCredit}単位</span>
                    <span class="category-display-in-cell">${classGrade}年</span>
                    <button class="remove-button">&times;</button>
                `;

                this.appendChild(classItemInCell);
                this.classList.add('filled-primary');

                classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                recalculateTotalCredits();
            });
        });
    }

    function removeClassFromTimetable(event) {
        const classItemInCell = event.target.closest('.class-item-in-cell');
        const cell = event.target.closest('.time-slot');

        if (classItemInCell && cell) {
            classItemInCell.remove();
            if (!cell.querySelector('.class-item-in-cell')) {
                cell.classList.remove('filled-primary');
            }
            recalculateTotalCredits();
        }
    }

    function recalculateTotalCredits() {
        totalCredit = 0;
        if (!timetableTable) return;
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(item => {
            const creditText = item.querySelector('.class-credit-in-cell')?.textContent || "0単位";
            const credit = parseInt(creditText.replace('単位', ''), 10) || 0;
            totalCredit += credit;
        });
        updateAndDisplayTotalCredit();
    }

    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。ログイン後に時間割を保存できます。');
            window.location.href = 'login.php';
            return;
        }

        if (!timetableGradeSelect || !timetableTermSelect) {
            alert('時間割の保存に失敗しました: 時間割表示用の学年または学期セレクタが見つかりません。');
            console.error("エラー: 'timetableGradeSelect' または 'timetableTermSelect' の要素が見つかりません。HTMLを確認してください。");
            return;
        }

        const selectedTimetableGrade = timetableGradeSelect.value;
        const selectedTimetableTerm = timetableTermSelect.value;

        const timetableData = [];
        if (timetableTable) {
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
        }

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
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
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

    function loadTimetable() {
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        if (!timetableGradeSelect || !timetableTermSelect) {
            console.warn("時間割のロードに失敗: 時間割表示用の学年または学期セレクタが見つかりません。");
            return;
        }

        // ★ ここにデバッグログ追加 ★
        console.log("loadTimetable() 呼び出し:", {
            timetableGradeValue: timetableGradeSelect.value,
            timetableTermValue: timetableTermSelect.value
        });

        const targetGrade = timetableGradeSelect.value.trim();
        const targetTerm = timetableTermSelect.value.trim();

        if (targetGrade === "" || targetTerm === "") {
            console.warn("時間割のロードに失敗: 学年と学期を選択してください");
            return;
        }

        totalCredit = 0;
        updateAndDisplayTotalCredit();

        if (timetableTable) {
            timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
                itemInCell.remove();
            });
            timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                cell.classList.remove('filled-primary');
            });
        }

        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${encodeURIComponent(targetGrade)}&timetable_term=${encodeURIComponent(targetTerm)}`)
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
                        const targetCell = timetableTable ? timetableTable.querySelector(cellSelector) : null;

                        if (targetCell) {
                            const className = entry.class_name || '不明な授業';
                            const classCredit = parseInt(entry.class_credit, 10) || 0;
                            const classOriginalGrade = entry.class_original_grade || '';

                            const classItemInCell = document.createElement('div');
                            classItemInCell.classList.add('class-item-in-cell');

                            classItemInCell.dataset.classId = entry.class_id;
                            classItemInCell.dataset.day = entry.day;
                            classItemInCell.dataset.period = entry.period;

                            classItemInCell.innerHTML = `
                                <span class="class-name-in-cell">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classOriginalGrade}年</span>
                                <button class="remove-button">&times;</button>
                            `;
                            targetCell.appendChild(classItemInCell);
                            targetCell.classList.add('filled-primary');

                            classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                            totalCredit += classCredit;
                        } else {
                            console.warn(`時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period}`);
                        }
                    });
                    updateAndDisplayTotalCredit();
                    console.log(`時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。`);
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
            });
    }

    function updateAndDisplayTotalCredit() {
        if (currentTotalCreditSpan) {
            currentTotalCreditSpan.textContent = totalCredit;
        }
    }

    // =========================================================
    // 4. イベントリスナー登録と初期実行
    // =========================================================

    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchAndDisplayClasses();
        });
    } else {
        console.warn("警告: 'classFilterForm' の要素が見つかりません。フィルター機能が動作しません。");
    }

    if (gradeSelect && termSelect) {
        fetchAndDisplayClasses();
    } else {
        console.warn("警告: 初期授業表示に必要なフィルター要素が見つからないため、授業リストの自動ロードは行われません。");
    }

    addDropListeners();

    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    } else {
        console.warn("警告: 'saveTimetableBtn' の要素が見つかりません。時間割保存機能が動作しません。");
    }

    if (timetableGradeSelect) {
        timetableGradeSelect.addEventListener('change', loadTimetable);
    }
    if (timetableTermSelect) {
        timetableTermSelect.addEventListener('change', loadTimetable);
    }

    if (currentUserId !== null) {
        if (timetableGradeSelect && timetableTermSelect) {
            loadTimetable();
        } else {
            console.warn("警告: ログインユーザーの時間割自動ロードに必要な時間割選択要素が見つかりません。");
        }
    }
});
