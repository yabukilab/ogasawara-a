document.addEventListener('DOMContentLoaded', () => {
    const classListDiv = document.getElementById('classList');
    const myTimetable = document.getElementById('myTimetable');
    const totalCreditsSpan = document.getElementById('totalCredits');
    const saveTimetableBtn = document.getElementById('saveTimetableBtn');
    const viewConfirmedTimetableBtn = document.getElementById('viewConfirmedTimetableBtn');
    const checkCreditsBtn = document.getElementById('checkCreditsBtn');

    const filterGradeSelect = document.getElementById('filterGrade');
    const filterTermSelect = document.getElementById('filterTerm');
    const filterCategorySelect = document.getElementById('filterCategory');

    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    let allClasses = []; // 모든 수업 데이터를 저장할 배열

    // 드래그 중인 요소를 저장할 변수
    let draggedClassItem = null;

    // 초기 로드: 모든 수업 데이터와 시간표를 불러옵니다.
    loadClasses();
    // 페이지 로드 시, PHP에서 설정한 초기 학년/학기로 시간표 로드
    // 스크린샷 125805.png를 보면 1학년/前期가 기본으로 설정되어 있음
    const initialGrade = timetableGradeSelect.value;
    const initialTerm = timetableTermSelect.value;
    loadTimetable(initialGrade, initialTerm);


    // 이벤트 리스너 설정
    filterGradeSelect.addEventListener('change', filterClasses);
    filterTermSelect.addEventListener('change', filterClasses);
    filterCategorySelect.addEventListener('change', filterClasses);

    timetableGradeSelect.addEventListener('change', () => {
        loadTimetable(timetableGradeSelect.value, timetableTermSelect.value);
    });
    timetableTermSelect.addEventListener('change', () => {
        loadTimetable(timetableGradeSelect.value, timetableTermSelect.value);
    });

    saveTimetableBtn.addEventListener('click', saveTimetable);
    viewConfirmedTimetableBtn.addEventListener('click', () => {
        window.location.href = 'confirmed_timetable.php';
    });
    checkCreditsBtn.addEventListener('click', () => {
        alert('単位取得状況を確認する機能はまだ実装されていません。');
    });

    // 수업 목록 로드 함수
    function loadClasses() {
        fetch('get_classes.php')
            .then(response => {
                if (!response.ok) {
                    // 404 에러 등 HTTP 에러 발생 시
                    return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}, Response: ${text}`); });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    allClasses = data.classes;
                    populateCategoryFilter(allClasses);
                    filterClasses(); // 필터링하여 표시
                } else {
                    classListDiv.innerHTML = `<p style="color: red;">授業リストの読み込みに失敗しました: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('授業リスト取得エラー:', error); // console.error로 출력
                classListDiv.innerHTML = `<p style="color: red;">授業リストの取得中にエラーが発生しました。</p>`;
            });
    }

    // 카테고리 필터 드롭다운 채우기
    function populateCategoryFilter(classes) {
        const categories = new Set();
        classes.forEach(cls => {
            if (cls.category1) categories.add(cls.category1);
            if (cls.category2) categories.add(cls.category2);
            if (cls.category3) categories.add(cls.category3);
        });

        filterCategorySelect.innerHTML = '<option value="">全て</option>';
        Array.from(categories).sort().forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            filterCategorySelect.appendChild(option);
        });
    }

    // 수업 필터링 및 표시 함수
    function filterClasses() {
        const selectedGrade = filterGradeSelect.value;
        const selectedTerm = filterTermSelect.value;
        const selectedCategory = filterCategorySelect.value;

        classListDiv.innerHTML = ''; // 기존 목록 비우기

        const filteredClasses = allClasses.filter(cls => {
            const matchGrade = selectedGrade === '' || String(cls.grade) === selectedGrade;
            const matchTerm = selectedTerm === '' || cls.term === selectedTerm;
            const matchCategory = selectedCategory === '' ||
                                  (cls.category1 === selectedCategory || cls.category2 === selectedCategory || cls.category3 === selectedCategory);
            return matchGrade && matchTerm && matchCategory;
        });

        if (filteredClasses.length === 0) {
            classListDiv.innerHTML = '<p style="text-align: center; color: #777; padding: 20px;">該当する授業がありません。</p>';
        } else {
            filteredClasses.forEach(cls => {
                const classItem = document.createElement('div');
                classItem.classList.add('class-item');
                classItem.setAttribute('draggable', true);
                classItem.dataset.classId = cls.id;
                classItem.dataset.className = cls.name;
                classItem.dataset.classCredit = cls.credit;
                classItem.dataset.classCategory = cls.category1; // 편의상 첫 번째 카테고리만 저장

                classItem.innerHTML = `
                    <h3>${h(cls.name)}</h3>
                    <p>${h(cls.credit)}単位</p>
                    <p class="class-meta">学年: ${h(cls.grade)} / 学期: ${h(cls.term)} / 区分: ${h(cls.category1)}</p>
                `;
                classListDiv.appendChild(classItem);
            });

            // 드래그 이벤트 리스너 다시 등록
            document.querySelectorAll('.class-item').forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
            });
        }
    }


    // 드래그 시작 이벤트 핸들러
    function handleDragStart(e) {
        draggedClassItem = this;
        // 드래그 데이터 설정 (옵션: 실제 데이터는 draggedClassItem 변수를 통해 접근)
        e.dataTransfer.setData('text/plain', this.dataset.classId);
        e.dataTransfer.effectAllowed = 'move';
        this.classList.add('dragging'); // 드래그 중인 요소에 스타일 추가
    }

    // 드래그 오버/엔터 이벤트 핸들러
    myTimetable.querySelectorAll('.time-slot').forEach(slot => {
        slot.addEventListener('dragover', handleDragOver);
        slot.addEventListener('dragenter', handleDragEnter);
        slot.addEventListener('dragleave', handleDragLeave);
        slot.addEventListener('drop', handleDrop);
    });

    function handleDragOver(e) {
        e.preventDefault(); // 드롭을 허용하기 위해 기본 동작 방지
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over'); // 드래그 오버 시 스타일 추가
    }

    function handleDragLeave() {
        this.classList.remove('drag-over'); // 드래그 벗어날 때 스타일 제거
    }

    // 드롭 이벤트 핸들러
    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        if (draggedClassItem) {
            const classId = draggedClassItem.dataset.classId;
            const className = draggedClassItem.dataset.className;
            const classCredit = draggedClassItem.dataset.classCredit;
            const classCategory = draggedClassItem.dataset.classCategory;

            // 이미 수업이 있는 칸이면 드롭 방지 (또는 교체 로직)
            if (this.classList.contains('filled-primary')) {
                // 기존 수업 제거
                const existingClassId = this.dataset.classId;
                const existingClassCredit = parseFloat(this.dataset.classCredit);
                removeCourseFromCell(this); // 기존 수업 제거 함수 호출
                updateTotalCredits(-existingClassCredit); // 기존 학점 차감
            }

            // 수업을 칸에 추가
            this.innerHTML = `
                <div class="class-item-in-cell">
                    <div class="class-name-in-cell">${h(className)}</div>
                    <div class="class-credit-in-cell">${h(classCredit)}単位</div>
                    <div class="category-display-in-cell">${h(classCategory)}</div>
                    <button class="remove-button" data-class-id="${classId}">×</button>
                </div>
            `;
            this.classList.add('filled-primary');
            this.dataset.classId = classId; // 셀에 classId 저장
            this.dataset.classCredit = classCredit; // 셀에 학점 저장 (학점 계산용)

            updateTotalCredits(parseFloat(classCredit));

            // 제거 버튼 이벤트 리스너 등록
            const removeBtn = this.querySelector('.remove-button');
            if (removeBtn) {
                removeBtn.addEventListener('click', handleRemoveClass);
            }
        }
        draggedClassItem.classList.remove('dragging'); // 드래그 끝났으니 스타일 제거
        draggedClassItem = null; // 드래그 요소 초기화
    }

    // 수업 제거 핸들러
    function handleRemoveClass(e) {
        const cell = this.closest('.time-slot');
        const creditToRemove = parseFloat(cell.dataset.classCredit);
        if (cell) {
            removeCourseFromCell(cell);
            updateTotalCredits(-creditToRemove);
        }
    }

    // 셀에서 수업 제거 및 데이터 초기화
    function removeCourseFromCell(cell) {
        cell.innerHTML = '';
        cell.classList.remove('filled-primary');
        delete cell.dataset.classId;
        delete cell.dataset.classCredit;
    }


    // 총 학점 업데이트 함수
    function updateTotalCredits(creditChange) {
        let currentTotal = parseFloat(totalCreditsSpan.textContent);
        currentTotal += creditChange;
        totalCreditsSpan.textContent = currentTotal.toFixed(1); // 소수점 한 자리까지 표시
    }

    // 시간표 저장 함수
    function saveTimetable() {
        const userId = document.body.dataset.userId;
        if (userId === 'null' || !userId) {
            alert("ログインしていません。時間割を保存できません。");
            return;
        }

        // timetableGradeSelect와 timetableTermSelect에서 값을 가져옵니다.
        // 만약 선택된 값이 없다면 (예: 옵션이 비어있는 경우), 기본값을 설정합니다.
        const currentTimetableGrade = document.getElementById('timetableGradeSelect').value || '1'; // 기본값 '1' 설정
        const currentTimetableTerm = document.getElementById('timetableTermSelect').value || '前期'; // 기본값 '前期' 설정

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

        console.log("Saving data:", {
            grade: currentTimetableGrade,
            term: currentTimetableTerm,
            timetable: timetableData
        });

        fetch('save_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                grade: currentTimetableGrade,
                term: currentTimetableTerm,
                timetable: timetableData
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(`HTTP error! status: ${response.status}, Response: ${text}`); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('時間割が正常に保存されました。');
                loadTimetable(currentTimetableGrade, currentTimetableTerm);
            } else {
                alert('時間割の保存に失敗しました: ' + data.message);
                console.error('保存失敗メッセージ:', data.message);
            }
        })
        .catch(error => {
            console.error('時間割保存中にエラーが発生しました:', error);
            alert('時間割保存中にエラーが発生しました。詳細: ' + error.message);
        });
    }

    // 시간표 로드 함수
    function loadTimetable(grade, term) {
        const userId = document.body.dataset.userId;
        if (userId === 'null' || !userId) {
             console.warn("ログインしていないため、時間割をロードできません。");
             return;
        }

        // 기존 시간표 초기화
        document.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            removeCourseFromCell(cell);
        });
        updateTotalCredits(-parseFloat(totalCreditsSpan.textContent)); // 총 학점 0으로 초기화

        fetch(`get_timetable.php?grade=${encodeURIComponent(grade)}&term=${encodeURIComponent(term)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // 시간표 데이터를 기반으로 칸 채우기
                data.timetable.forEach(item => {
                    const cell = myTimetable.querySelector(`.time-slot[data-day="${item.day}"][data-period="${item.period}"]`);
                    if (cell) {
                        cell.innerHTML = `
                            <div class="class-item-in-cell">
                                <div class="class-name-in-cell">${h(item.class_name)}</div>
                                <div class="class-credit-in-cell">${h(item.credit)}単位</div>
                                <div class="category-display-in-cell">${h(item.category_name)}</div>
                                <button class="remove-button" data-class-id="${item.class_id}">×</button>
                            </div>
                        `;
                        cell.classList.add('filled-primary');
                        cell.dataset.classId = item.class_id;
                        cell.dataset.classCredit = item.credit;
                        cell.querySelector('.remove-button').addEventListener('click', handleRemoveClass);
                        updateTotalCredits(parseFloat(item.credit));
                    }
                });
            } else {
                console.error('時間割のロードに失敗しました:', data.message);
                // alert('時間割のロードに失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('時間割ロード中にエラーが発生しました:', error);
            // alert('時間割ロード中にエラーが発生しました。詳細: ' + error.message);
        });
    }

    // HTML 특수문자 이스케이프 함수 (PHP의 h()와 유사)
    // 이 함수는 PHP의 h()와 다르게 클라이언트 사이드에서만 사용됨
    function h(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});