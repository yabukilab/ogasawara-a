document.addEventListener('DOMContentLoaded', function () {
    let currentUserId = null;
    const bodyElement = document.body;
    const userIdFromDataAttribute = bodyElement.dataset.userId;

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10);
    } else {
        console.warn("警告: currentUserIdが定義されていません。ゲストモードで動作します。");
    }

    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const classListContainer = document.getElementById('lesson-list-container');
    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');
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
                        classItem.dataset.grade = cls.grade;

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

                if (this.querySelector('.class-item-in-cell')) {
                    alert('この時間枠にはすでに授業があります。');
                    return;
                }

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

                if (!countedClassIds.has(classId)) {
                    totalCredit += classCredit;
                    countedClassIds.add(classId);
                }
                updateAndDisplayTotalCredit();
            });
        });
    }

    function removeClassFromTimetable(event) {
        const classItemInCell = event.target.closest('.class-item-in-cell');
        const cell = event.target.closest('.time-slot');

        if (classItemInCell && cell) {
            const removedCreditSpan = classItemInCell.querySelector('.class-credit-in-cell');
            const classId = classItemInCell.dataset.classId;
            const removedCredit = parseInt(removedCreditSpan.textContent.replace('単位', ''), 10);

            // 同じ授業が他に残っていなければ減算
            const remaining = document.querySelectorAll(`.class-item-in-cell[data-class-id="${classId}"]`);
            if (remaining.length === 1) {
                totalCredit -= removedCredit;
                countedClassIds.delete(classId);
            }
            updateAndDisplayTotalCredit();

            classItemInCell.remove();
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
                    loadTimetable();
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
        countedClassIds.clear(); // 重複記録もリセット
        updateAndDisplayTotalCredit();

        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => itemInCell.remove());
        timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => cell.classList.remove('filled-primary'));

        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    data.timetable.forEach(entry => {
                        const selector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(selector);
                        if (!targetCell) return;

                        const classItemInCell = document.createElement('div');
                        classItemInCell.classList.add('class-item-in-cell');
                        classItemInCell.dataset.classId = entry.class_id;
                        classItemInCell.dataset.day = entry.day;
                        classItemInCell.dataset.period = entry.period;

                        const className = entry.class_name || '不明な授業';
                        const classCredit = parseInt(entry.class_credit, 10) || 0;
                        const classOriginalGrade = entry.class_original_grade || '';

                        classItemInCell.innerHTML = `
                            <span class="class-name-in-cell">${className}</span>
                            <span class="class-credit-in-cell">${classCredit}単位</span>
                            <span class="category-display-in-cell">${classOriginalGrade}年</span>
                            <button class="remove-button">&times;</button>
                        `;
                        targetCell.appendChild(classItemInCell);
                        targetCell.classList.add('filled-primary');

                        classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                        if (!countedClassIds.has(entry.class_id)) {
                            totalCredit += classCredit;
                            countedClassIds.add(entry.class_id);
                        }
                    });
                    updateAndDisplayTotalCredit();
                }
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
    }

    updateAndDisplayTotalCredit();
});
