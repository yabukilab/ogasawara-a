document.addEventListener('DOMContentLoaded', () => {
    const userId = document.body.dataset.userId; // body 태그에서 user_id 가져오기
    console.log("main_script.js 로드됨. 사용자 ID:", userId); // 디버깅용

    if (userId === 'null') {
        alert("ログインしていません。ログイン後に時間割を作成してください。");
        // 로그인 페이지로 리다이렉트하거나 기능 비활성화
        // window.location.href = 'login.php';
        // return;
    }

    const lessonListContainer = document.getElementById('lesson-list-container');
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeFilter = document.getElementById('gradeFilter');
    const termFilter = document.getElementById('termFilter');
    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableBtn = document.getElementById('saveTimetableBtn');
    const currentTotalCreditSpan = document.getElementById('current-total-credit');

    // 시간표 로드/저장 시 사용할 학년/학기 선택 드롭다운
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    let draggedClassId = null;

    // --- 이벤트 리스너 ---

    // 수업 목록 필터링
    classFilterForm.addEventListener('submit', (e) => {
        e.preventDefault();
        loadClassList(gradeFilter.value, termFilter.value);
    });

    // 드래그 시작 이벤트
    lessonListContainer.addEventListener('dragstart', (e) => {
        if (e.target.classList.contains('class-item')) {
            draggedClassId = e.target.dataset.classId;
            e.dataTransfer.setData('text/plain', draggedClassId);
            e.target.classList.add('dragging');
        }
    });

    // 드래그 종료 이벤트
    lessonListContainer.addEventListener('dragend', (e) => {
        if (e.target.classList.contains('class-item')) {
            e.target.classList.remove('dragging');
        }
    });

    // 드래그 오버 (드롭 가능한 영역 표시)
    timetableTable.addEventListener('dragover', (e) => {
        e.preventDefault(); // 드롭을 허용하기 위해 기본 동작 방지
        const targetCell = e.target.closest('.time-slot');
        if (targetCell) {
            document.querySelectorAll('.time-slot.drag-over').forEach(cell => {
                cell.classList.remove('drag-over');
            });
            targetCell.classList.add('drag-over');
        }
    });

    // 드래그 리브 (드롭 가능한 영역 표시 해제)
    timetableTable.addEventListener('dragleave', (e) => {
        const targetCell = e.target.closest('.time-slot');
        if (targetCell) {
            targetCell.classList.remove('drag-over');
        }
    });

    // 드롭 이벤트
    timetableTable.addEventListener('drop', handleDrop);

    // 시간표 셀 내부의 삭제 버튼 클릭 이벤트
    timetableTable.addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-button')) {
            const button = e.target;
            const classIdToRemove = button.dataset.classId;
            const classItemInCell = button.closest('.class-item-in-cell');
            const targetCell = classItemInCell ? classItemInCell.closest('.time-slot') : null;

            if (targetCell) {
                targetCell.innerHTML = ''; // 셀 내용 비우기
                targetCell.classList.remove('filled-primary'); // 스타일 클래스 제거
                targetCell.dataset.classId = ''; // 저장된 classId 제거
                updateTotalCredit(); // 총 단위수 업데이트
            }
        }
    });

    // 시간표 저장 버튼 클릭
    saveTimetableBtn.addEventListener('click', saveTimetable);

    // 시간표 학년/학기 선택 변경 시 시간표 다시 로드
    timetableGradeSelect.addEventListener('change', () => {
        loadTimetable(timetableGradeSelect.value, timetableTermSelect.value);
    });
    timetableTermSelect.addEventListener('change', () => {
        loadTimetable(timetableGradeSelect.value, timetableTermSelect.value);
    });

    // --- 함수 정의 ---

    // 수업 목록 로드
    function loadClassList(grade = '', term = '') {
        fetch(`get_classes.php?grade=${grade}&term=${term}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                lessonListContainer.innerHTML = ''; // 기존 목록 초기화
                if (data.success) {
                    if (data.classes.length > 0) {
                        data.classes.forEach(cls => {
                            const classItemDiv = document.createElement('div');
                            classItemDiv.classList.add('class-item');
                            classItemDiv.setAttribute('draggable', 'true');
                            classItemDiv.dataset.classId = cls.id; // DB ID 저장

                            classItemDiv.innerHTML = `
                                <div class="lesson-name">${cls.name}</div>
                                <div class="lesson-details">
                                    ${cls.credit}単位 (${cls.term}・${cls.grade}年)
                                    ${cls.category1 ? ` / ${cls.category1}` : ''}
                                </div>
                            `;
                            lessonListContainer.appendChild(classItemDiv);
                        });
                    } else {
                        lessonListContainer.innerHTML = '<p>条件に合う授業が見つかりませんでした。</p>';
                    }
                } else {
                    lessonListContainer.innerHTML = `<p>授業の読み込みに失敗しました: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('授業リストの取得中にエラーが発生しました:', error);
                lessonListContainer.innerHTML = `<p>授業リストの取得中にエラーが発生しました: ${error.message}</p>`;
            });
    }

    // 드롭 처리 함수
    function handleDrop(e) {
        e.preventDefault();
        const draggableClassId = e.dataTransfer.getData('text/plain');
        const draggedItem = document.querySelector(`.class-item[data-class-id="${draggableClassId}"]`);

        const targetCell = e.target.closest('.time-slot');

        if (targetCell && draggedItem) {
            // 해당 셀에 이미 수업이 있다면 경고 또는 덮어쓰기 로직
            if (targetCell.dataset.classId && targetCell.dataset.classId !== draggableClassId) {
                if (!confirm('このコマにはすでに授業があります。上書きしますか？')) {
                    targetCell.classList.remove('drag-over');
                    return; // 사용자가 취소하면 드롭 중단
                }
            }
            
            // **기존 내용 제거 (핵심)**
            targetCell.innerHTML = ''; 
            targetCell.classList.remove('filled-primary');
            targetCell.dataset.classId = ''; // 기존 ID 제거

            const className = draggedItem.querySelector('.lesson-name').textContent;
            // 정규식을 사용하여 "X単位"에서 X 값만 추출
            const creditMatch = draggedItem.querySelector('.lesson-details').textContent.match(/(\d+)単位/);
            const credit = creditMatch ? creditMatch[1] : '不明'; // 매칭되지 않으면 '不明'
            
            // 카테고리 정보 추출 (예: ' / 分類1' 형태에서 '分類1'만 추출)
            const categoryMatch = draggedItem.querySelector('.lesson-details').textContent.match(/\/ (.+)/);
            const category = categoryMatch ? categoryMatch[1].trim() : ''; // 없으면 빈 문자열

            const classItemHtml = `
                <div class="class-item-in-cell" draggable="true" data-class-id="${draggableClassId}">
                    <div class="class-name-in-cell">${className}</div>
                    <div class="class-credit-in-cell">${credit}単位</div>
                    <div class="category-display-in-cell">${category}</div>
                    <button class="remove-button" data-class-id="${draggableClassId}">&times;</button>
                </div>
            `;
            targetCell.innerHTML = classItemHtml;
            targetCell.classList.add('filled-primary');
            targetCell.dataset.classId = draggableClassId; // 셀에 classId 저장
            
            updateTotalCredit(); // 총 단위수 업데이트
        }
        // 드래그 오버 상태 해제
        document.querySelectorAll('.time-slot.drag-over').forEach(cell => {
            cell.classList.remove('drag-over');
        });
    }

    // 총 단위수 업데이트 함수
    function updateTotalCredit() {
        let totalCredit = 0;
        document.querySelectorAll('.class-item-in-cell').forEach(item => {
            const creditText = item.querySelector('.class-credit-in-cell').textContent;
            const creditMatch = creditText.match(/(\d+)単位/);
            if (creditMatch) {
                totalCredit += parseInt(creditMatch[1]);
            }
        });
        currentTotalCreditSpan.textContent = totalCredit;
    }

    // 시간표 저장 함수
    function saveTimetable() {
        if (userId === 'null') {
            alert("ログインしていません。時間割を保存できません。");
            return;
        }

        const currentTimetableGrade = timetableGradeSelect.value;
        const currentTimetableTerm = timetableTermSelect.value;
        const timetableData = [];

        document.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            const classId = cell.dataset.classId;
            const day = cell.dataset.day;
            const period = cell.dataset.period;
            if (classId) {
                timetableData.push({
                    class_id: classId,
                    day: day,
                    period: period
                });
            }
        });

        fetch('save_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                user_id: userId,
                grade: currentTimetableGrade,
                term: currentTimetableTerm,
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
            if (data.success) {
                alert('時間割が正常に保存されました。');
            } else {
                alert('時間割の保存に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('時間割保存中にエラーが発生しました:', error);
            alert('時間割保存中にエラーが発生しました。');
        });
    }

    // 시간표 로드 함수
    function loadTimetable(grade, term) {
        if (userId === 'null') {
            // alert("ログインしていません。時間割を読み込めません。");
            // 로그인하지 않은 경우 시간표를 비워두거나, 경고만 하고 진행
            document.querySelectorAll('.time-slot').forEach(cell => {
                cell.innerHTML = '';
                cell.classList.remove('filled-primary');
                cell.dataset.classId = '';
            });
            updateTotalCredit();
            return;
        }

        fetch(`get_timetable.php?user_id=${userId}&grade=${grade}&term=${term}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                // **모든 시간표 셀 초기화 (핵심)**
                document.querySelectorAll('.time-slot').forEach(cell => {
                    cell.innerHTML = '';
                    cell.classList.remove('filled-primary');
                    cell.dataset.classId = '';
                });

                if (data.success && data.timetable.length > 0) {
                    data.timetable.forEach(item => {
                        const targetCell = document.querySelector(`.time-slot[data-day="${item.day}"][data-period="${item.period}"]`);
                        if (targetCell) {
                            const classItemHtml = `
                                <div class="class-item-in-cell" draggable="true" data-class-id="${item.class_id}">
                                    <div class="class-name-in-cell">${item.class_name}</div>
                                    <div class="class-credit-in-cell">${item.credit}単位</div>
                                    <div class="category-display-in-cell">${item.category_name}</div>
                                    <button class="remove-button" data-class-id="${item.class_id}">&times;</button>
                                </div>
                            `;
                            targetCell.innerHTML = classItemHtml;
                            targetCell.classList.add('filled-primary');
                            targetCell.dataset.classId = item.class_id;
                        } else {
                            console.warn(`時間割セルが見つかりませんでした: 日曜=${item.day}, 時限=${item.period}`);
                        }
                    });
                } else if (!data.success) {
                    console.error('時間割の読み込みに失敗しました:', data.message);
                } else {
                    console.log('保存された時間割がありません。');
                }
                updateTotalCredit();
            })
            .catch(error => {
                console.error('時間割読み込み中にエラーが発生しました:', error);
                // alert('時間割読み込み中にエラーが発生しました。');
                // 오류 발생 시에도 셀 초기화 및 단위 초기화
                document.querySelectorAll('.time-slot').forEach(cell => {
                    cell.innerHTML = '';
                    cell.classList.remove('filled-primary');
                    cell.dataset.classId = '';
                });
                updateTotalCredit();
            });
    }

    // 초기 로드
    loadClassList(gradeFilter.value, termFilter.value);
    loadTimetable(timetableGradeSelect.value, timetableTermSelect.value); // 페이지 로드 시 현재 선택된 학년/학기로 시간표 로드
});