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
    // const category1Filter = document.getElementById('category1Filter'); // category1 필터 제거

    const classListContainer = document.getElementById('lesson-list-container');

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 새로 추가된 학년 선택 드롭다운 (index.php에서 추가됨)
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    // 새로 추가된 학기 선택 드롭다운 (index.php에서 추가됨)
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    let draggedClass = null; // 드래그 중인 수업 데이터를 저장할 변수

    // --- 추가: 총 학점 관리 변수 및 표시 DOM 요소 ---
    let totalCredit = 0;
    const currentTotalCreditSpan = document.getElementById('current-total-credit'); // 추가된 HTML 요소
    // --- 추가 끝 ---

    // =========================================================
    // 3. 함수 정의
    // =========================================================

    // --- 3.1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        const grade = gradeSelect.value;
        const term = termSelect.value;
        // const category1 = category1Filter?.value || ''; // category1 필터 제거

        fetch(show_lessons.php?grade=${grade}&term=${term}) // category1 파라미터 제거
            .then(response => {
                if (!response.ok) {
                    throw new Error(HTTP error! status: ${response.status});
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
                        classItem.dataset.category1 = cls.category1; // class 테이블에서 읽어오지만 필터링에는 사용 안함
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3;

                        classItem.innerHTML = 
                            <div class="lesson-name">${cls.name}</div>
                            <div class="lesson-details">
                                <span class="lesson-credit">${cls.credit}単位</span>
                            </div>
                        ;
                        classListContainer.appendChild(classItem);
                    });
                    addDragListeners();
                } else {
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    classListContainer.innerHTML = <p class="message error">${data.message}</p>;
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

                // =====================================================================
                // ***** 하나의 셀에 하나의 수업만 드롭되도록 강제하는 로직 추가/수정 *****
                // 이 셀에 이미 .class-item-in-cell 클래스를 가진 자식 요소가 있는지 확인
                if (this.querySelector('.class-item-in-cell')) {
                    alert('この時間枠にはすでに授業があります。新しい授業を追加する前に、既存の授業を削除してください。');
                    return; // 이미 수업이 있으면 추가하지 않고 함수 종료
                }
                // =====================================================================

                const classId = draggedClass.dataset.id;
                const className = draggedClass.dataset.name;
                const classCredit = parseInt(draggedClass.dataset.credit, 10); // 학점을 숫자로 변환
                const classGrade = draggedClass.dataset.grade;
                // const classCategory2 = draggedClass.dataset.category2; // 필요하면 사용

                // 새로운 수업 아이템 요소 생성
                const classItemInCell = document.createElement('div');
                classItemInCell.classList.add('class-item-in-cell');
                classItemInCell.setAttribute('draggable', true); // 셀 안의 아이템도 드래그 가능하게 할 경우

                // 중요: 셀의 data-day와 data-period를 직접 읽어와서 classItemInCell에 저장
                // 이렇게 하면 나중에 saveTimetable 함수에서 이 값을 정확하게 읽을 수 있습니다.
                classItemInCell.dataset.classId = classId;
                classItemInCell.dataset.day = this.dataset.day;    // <-- 셀의 data-day 값을 가져옴
                classItemInCell.dataset.period = this.dataset.period; // <-- 셀의 data-period 값을 가져옴

                classItemInCell.innerHTML = 
                    <span class="class-name-in-cell">${className}</span>
                    <span class="class-credit-in-cell">${classCredit}単位</span>
                    <span class="category-display-in-cell">${classGrade}年</span>
                    <button class="remove-button">&times;</button>
                ;
                
                // 기존 innerHTML = ... 대신 appendChild 사용
                this.appendChild(classItemInCell);
                this.classList.add('filled-primary');

                // 삭제 버튼에 이벤트 리스너 추가 (새롭게 생성된 버튼에 연결)
                classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                // --- 총 학점에 현재 수업의 학점을 더하고 표시 ---
                totalCredit += classCredit;
                updateAndDisplayTotalCredit();
                // --- 추가 끝 ---
            });
        });
    }

    // --- 3.4. 時間割から授業削除 ---
    function removeClassFromTimetable(event) {
        const classItemInCell = event.target.closest('.class-item-in-cell'); // .class-item-in-cell을 찾음
        const cell = event.target.closest('.time-slot'); // 부모 .time-slot을 찾음

        if (classItemInCell && cell) {
            const removedCreditSpan = classItemInCell.querySelector('.class-credit-in-cell'); // .class-item-in-cell 내에서 찾음
            if (removedCreditSpan) {
                const removedCreditText = removedCreditSpan.textContent;
                const removedCredit = parseInt(removedCreditText.replace('単位', ''), 10);
                if (!isNaN(removedCredit)) {
                    totalCredit -= removedCredit;
                    updateAndDisplayTotalCredit();
                }
            }
            // classItemInCell 자체를 제거
            classItemInCell.remove();
            // 셀이 비었는지 확인하고 filled-primary 클래스 제거
            if (!cell.querySelector('.class-item-in-cell')) {
                cell.classList.remove('filled-primary');
            }
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
        // .filled-primary 셀이 아니라, 그 안에 있는 .class-item-in-cell을 모두 찾습니다.
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
            const classId = itemInCell.dataset.classId;
            const day = itemInCell.dataset.day;     // <-- class-item-in-cell에 저장된 data-day를 읽음
            const period = itemInCell.dataset.period; // <-- class-item-in-cell에 저장된 data-period를 읽음

            timetableData.push({
                class_id: classId,
                day_of_week: day, // 이 값이 save_timetable.php로 전달됨
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
                throw new Error(HTTP error! status: ${response.status});
            }
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                alert('時間割が正常に保存されました！');
                // 저장 후 현재 선택된 학년/학기 시간표를 다시 로드
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

    // --- 3.6. 保存された時間割読み込み (timetable_grade, timetable_term 추가) ---
    function loadTimetable() { // 파라미터 제거, 내부에서 직접 현재 선택된 값 사용
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        const targetGrade = timetableGradeSelect.value;
        const targetTerm = timetableTermSelect.value; // 현재 선택된 학기 사용

        // --- 수정: 시간표 로드 전에 총 학점 초기화 ---
        totalCredit = 0; // 로드 전에 초기화
        updateAndDisplayTotalCredit(); // 초기화된 값 바로 반영
        // --- 수정 끝 ---

        // 기존 시간표 초기화: 모든 .class-item-in-cell 요소를 제거하고 .filled-primary 클래스 제거
        timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
            itemInCell.remove();
        });
        timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            cell.classList.remove('filled-primary');
        });

        fetch(get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}) // <-- 학년 및 학기 정보 추가
            .then(response => {
                if (!response.ok) {
                    throw new Error(HTTP error! status: ${response.status});
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    data.timetable.forEach(entry => {
                        // DB에서 가져온 entry.day (일본어 요일)와 HTML 셀의 data-day (일본어 요일) 매칭
                        const cellSelector = .time-slot[data-day="${entry.day}"][data-period="${entry.period}"];
                        const targetCell = timetableTable.querySelector(cellSelector);

                        if (targetCell) {
                            const className = entry.class_name || '不明な授業';
                            const classCredit = parseInt(entry.class_credit, 10) || 0; // 학점을 숫자로 변환
                            const classOriginalGrade = entry.class_original_grade || ''; // 수업의 실제 학년

                            // 새 class-item-in-cell 요소를 생성하여 추가
                            const classItemInCell = document.createElement('div');
                            classItemInCell.classList.add('class-item-in-cell');
                            // 로드 시에도 data-day와 data-period를 class-item-in-cell에 저장하여 일관성 유지
                            classItemInCell.dataset.classId = entry.class_id;
                            classItemInCell.dataset.day = entry.day;    // DB에서 가져온 요일 저장
                            classItemInCell.dataset.period = entry.period; // DB에서 가져온 교시 저장

                            classItemInCell.innerHTML = 
                                <span class="class-name-in-cell">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classOriginalGrade}年</span>
                                <button class="remove-button">&times;</button>
                            ;
                            targetCell.appendChild(classItemInCell);
                            targetCell.classList.add('filled-primary');

                            classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                            // --- 추가: 로드된 수업의 학점을 총 학점에 더하기 ---
                            totalCredit += classCredit;
                            // --- 추가 끝 ---
                        } else {
                            console.warn(時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period});
                        }
                    });
                    // --- 추가: 모든 수업 로드 후 총 학점 최종 업데이트 ---
                    updateAndDisplayTotalCredit();
                    // --- 추가 끝 ---
                    console.log(時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。);
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
                // alert('時間割のロード中にエラーが発生しました。ネットワーク接続を確認してください。'); // 너무 자주 뜨지 않도록 경고 대신 콘솔 출력
            });
    }

    // --- 헬퍼 함수: 총 학점 업데이트 및 표시 ---
    function updateAndDisplayTotalCredit() {
        if (currentTotalCreditSpan) {
            currentTotalCreditSpan.textContent = totalCredit;
        }
    }
    // --- 헬퍼 함수 끝 ---

    // =========================================================
    // 4. 이벤트리스너 등록과 초기 실행
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

    // 초기 로드 시 총 학점 표시 (필요시)
    // loadTimetable()이 이미 호출되므로, 그 안에서 초기화 및 표시가 이루어질 것입니다.
    // 만약 로그인하지 않은 상태에서 빈 시간표가 먼저 보인다면, 여기서 한 번 더 호출할 수 있습니다.
    updateAndDisplayTotalCredit();

}); // DOMContentLoaded 閉じ括弧