// 전역 변수들
let timetableData = {}; // 時間割データを保持するオブジェクト
let currentSelectedClass = null; // 現在選択されている授業のデータ
let totalCredits = 0; // 合計単位数
let addedClassCreditsMap = new Map(); // 各授業IDの学点を追跡し、重複加算を防ぐ

// 요일과 시한 매핑 (편의를 위해 전역으로 정의)
const DAYS = ['月', '火', '水', '木', '金', '土'];
const PERIODS = Array.from({length: 10}, (_, i) => i + 1); // 1から10まで

// ページロード時の初期化
document.addEventListener('DOMContentLoaded', () => {
    // PHPから渡されたユーザーの時間割データをJSのtimetableData形式に変換し初期化
    initializeTimetableData(userTimetableData);
    renderTimetable(); // 時間割の初期描画 (PHP가 생성한 구조를 재활용)

    // 授業リストはPHP에서 이미 렌더링되었다고 가정합니다.
    // 만약 JS가 렌더링해야 한다면 아래 주석 해제 및 allClasses 변수 확인
    // renderClassList(); 

    updateFilterDisplay(); // フィルター表示を更新
    updateButtonStates(); // ログイン状態に応じてボタンの有効/無効を切り替える

    // 授業リストのクリックイベントリスナーを設定 (이벤트 위임)
    const classListTable = document.getElementById('classListTable');
    if (classListTable) {
        classListTable.addEventListener('click', (event) => {
            const target = event.target;
            // 「選択」ボタンがクリックされたかチェック
            if (target.tagName === 'BUTTON' && target.classList.contains('select-class-btn')) {
                const row = target.closest('tr');
                if (row) {
                    const classId = row.dataset.classId; // dataset 사용
                    selectClass(classId);
                }
            }
        });
    } else {
        console.error("classListTable element not found.");
    }

    // 時間割に追加ボタンのクリックイベントリスナー
    const addClassBtn = document.getElementById('add_class_btn');
    if (addClassBtn) {
        addClassBtn.addEventListener('click', addClassToTimetable);
    } else {
        console.error("add_class_btn element not found.");
    }

    // 時間割のセルクリックイベントリスナー (授業削除用)
    // #timetable ID가 HTML <table>에 부여되어 있어야 합니다.
    const timetableElement = document.getElementById('timetable');
    if (timetableElement) {
        // 이벤트 위임: 테이블 전체에 리스너를 달고 클릭된 요소를 확인
        timetableElement.addEventListener('click', (event) => {
            const target = event.target.closest('.class-in-cell'); // 수업 정보가 표시된 div 자체를 클릭했을 때
            if (target) { // 수업 정보 div를 클릭했다면
                 const cell = target.closest('td'); // 부모 td 요소를 찾음
                 if (cell && cell.classList.contains('filled-cell')) {
                    const cellId = cell.id; // 例: cell-月-1
                    const parts = cellId.split('-'); // ['cell', '月', '1']
                    const day = parts[1];
                    const period = parseInt(parts[2]);

                    if (confirm(`${day}曜日の${period}時限の授業を削除しますか？`)) {
                        removeClassFromTimetable(day, period);
                    }
                 }
            }
        });
    } else {
        console.error("Timetable element with ID 'timetable' not found.");
    }

    // 時間割を確定して保存ボタンのクリックイベントリスナー
    const confirmTimetableBtn = document.getElementById('confirmTimetableBtn');
    if (confirmTimetableBtn) {
        confirmTimetableBtn.addEventListener('click', saveTimetable);
    } else {
        console.error("confirmTimetableBtn element not found.");
    }
});

// PHPから渡されたユーザーの時間割データをJSのtimetableData形式に変換し初期化
function initializeTimetableData(data) {
    timetableData = {}; // まずは空にする
    totalCredits = 0; // 初期化時にも合計単位数 초기화
    addedClassCreditsMap = new Map(); // 학점 맵도 초기화

    if (!data || data.length === 0) {
        console.log("No initial timetable data to load.");
        return;
    }

    // PHP에서 넘어온 데이터는 이미 각 칸(day, period)에 대한 수업 정보가 있다고 가정
    data.forEach(entry => {
        const day = entry.day;
        const period = entry.period;
        
        // 유효한 요일, 시한인지 확인
        if (!DAYS.includes(day) || !PERIODS.includes(period)) {
            console.warn(`Invalid day or period in initial data: ${day}, ${period}`);
            return;
        }

        if (!timetableData[day]) {
            timetableData[day] = {};
        }
        
        // 각 셀의 정보를 저장
        timetableData[day][period] = {
            id: entry.class_id,
            name: entry.className, // PHP에서 `c.name as className` 등으로 가져왔을 것으로 예상
            credit: entry.classCredit, // PHP에서 `c.credit as classCredit` 등으로 가져왔을 것으로 예상
            term: entry.classTerm // PHP에서 `c.term as classTerm` 등으로 가져왔을 것으로 예상
        };

        // 학점은 수업 ID당 한 번만 더합니다.
        if (!addedClassCreditsMap.has(entry.class_id)) {
            totalCredits += entry.classCredit;
            addedClassCreditsMap.set(entry.class_id, entry.classCredit);
        }
    });
    console.log("Initialized timetableData:", timetableData);
    console.log("Initial totalCredits:", totalCredits);
}


// フィルター表示を更新する関数
function updateFilterDisplay() {
    let displayGradeText;
    if (currentSelectedGradeFromPHP == 0) {
        displayGradeText = '全て';
    } else {
        displayGradeText = `${currentSelectedGradeFromPHP}年生`;
    }

    let displayTermText;
    switch (parseInt(currentSelectedTermFromPHP)) { // PHP에서 넘어온 문자열일 수 있으니 parseInt
        case 0: displayTermText = ' / 全て'; break;
        case 1: displayTermText = ' / 前期'; break;
        case 2: displayTermText = ' / 後期'; break;
        default: displayTermText = ''; break;
    }
    
    const displayGradeElement = document.getElementById('displayGrade');
    const displayTermElement = document.getElementById('displayTerm');

    if (displayGradeElement) {
        displayGradeElement.textContent = displayGradeText;
    }
    if (displayTermElement) {
        displayTermElement.textContent = displayTermText;
    }
}


// ログイン状態に基づいてボタンの有効/無効を切り替える
function updateButtonStates() {
    const confirmButton = document.getElementById('confirmTimetableBtn');
    if (confirmButton) {
        if (isUserLoggedIn) {
            confirmButton.disabled = false;
            confirmButton.classList.remove('disabled-button');
        } else {
            confirmButton.disabled = true;
            confirmButton.classList.add('disabled-button');
        }
    }
}


// 授業リストを描画する関数 (PHP에서 렌더링된다면 이 함수는 필요 없음)
// 이전에 renderClassList() 함수를 작성했지만, 사용자 설명에 따르면 수업 목록은 PHP가 이미 렌더링하는 것 같습니다.
// 따라서 이 함수는 주석 처리하거나 삭제합니다.
/*
function renderClassList() {
    const classListBody = document.querySelector('#classListTable tbody');
    if (!classListBody) {
        console.error("#classListTable tbody not found. Cannot render class list.");
        return;
    }
    classListBody.innerHTML = ''; // 既存のリストをクリア

    const classesToRender = allClasses; // PHP에서 넘어온 allClasses 변수 사용

    if (!classesToRender || classesToRender.length === 0) {
        classListBody.innerHTML = '<tr><td colspan="4" style="text-align: center; padding: 20px;">条件に合う授業が見つかりませんでした。</td></tr>';
        return;
    }

    classesToRender.forEach(classItem => {
        const row = classListBody.insertRow();
        row.dataset.classId = classItem.id; // row에 classId 데이터 속성 추가
        row.insertCell().textContent = classItem.name;
        row.insertCell().textContent = classItem.credit;
        row.insertCell().textContent = getTermName(classItem.term);

        const selectCell = row.insertCell();
        const selectButton = document.createElement('button');
        selectButton.textContent = '選択';
        selectButton.classList.add('select-class-btn');
        selectButton.dataset.classId = classItem.id; // 버튼에도 classId 데이터 속성 추가
        selectCell.appendChild(selectButton);
    });
}
*/

// 学期番号を学期名に変換するヘルパー関数
function getTermName(term_num) {
    switch (term_num) {
        case 1: return "前期";
        case 2: return "後期";
        default: return "不明";
    }
}


// 授業が選択されたときの処理
function selectClass(classId) {
    // allClasses 배열에서 선택된 수업을 찾습니다.
    currentSelectedClass = allClasses.find(c => c.id == classId);

    if (currentSelectedClass) {
        document.getElementById('currentSelectedClassName').textContent = currentSelectedClass.name;
        document.getElementById('currentSelectedClassCredit').textContent = currentSelectedClass.credit;
        // 수업은 항상 2시간 진행되므로, 고정된 텍스트를 표시
        document.getElementById('currentSelectedClassDurationFixed').textContent = "2時限"; 
    } else {
        document.getElementById('currentSelectedClassName').textContent = 'なし';
        document.getElementById('currentSelectedClassCredit').textContent = '0';
        document.getElementById('currentSelectedClassDurationFixed').textContent = '0時限';
        console.warn("Selected class not found in allClasses:", classId);
    }
}

// 時間割に授業を追加する関数
function addClassToTimetable() {
    if (!currentSelectedClass) {
        alert('先に授業を選択してください。');
        return;
    }

    const day = document.getElementById('day_select').value;
    const startPeriod = parseInt(document.getElementById('time_select').value);

    // 유효성 검사: 요일과 시작 시한이 선택되었는지 확인
    if (!day || isNaN(startPeriod)) {
        alert('曜日と時限を選択してください。');
        return;
    }

    // 수업은 항상 2시간 진행되므로, 시작 시한과 다음 시한을 계산
    const periodsToAdd = [startPeriod, startPeriod + 1];

    // 시간표 범위 (1~10교시)를 벗어나는지 확인
    if (periodsToAdd[1] > 10) {
        alert(`${day}曜日 ${startPeriod}時限から2時間授業を登録することはできません。時限の範囲を超えています (1-10時限)。`);
        return;
    }

    let conflict = false;
    let existingClassInfo = null; // 충돌하는 수업의 정보

    // 충돌 여부 사전 확인
    for (const p of periodsToAdd) {
        if (timetableData[day] && timetableData[day][p]) {
            conflict = true;
            existingClassInfo = timetableData[day][p]; // 어떤 수업과 충돌하는지 저장
            break; // 하나라도 충돌하면 바로 break
        }
    }

    if (conflict) {
        let confirmOverwrite = false;
        if (existingClassInfo && existingClassInfo.id === currentSelectedClass.id) {
             // 같은 수업을 다시 추가하려는 경우 (예: 실수로 두 번 클릭)
             alert('この授業はすでに選択した時限に登録されています。');
             return;
        } else {
            confirmOverwrite = confirm(`選択した時限 (${day}曜日 ${periodsToAdd.join(',')}時限) にはすでに授業があります。\n上書きしますか？`);
        }
        
        if (!confirmOverwrite) {
            return; // 사용자가 취소하면 함수 종료
        }
        
        // 덮어쓰기 허용 시: 기존 수업 학점 제거 (중복 방지)
        if (existingClassInfo && addedClassCreditsMap.has(existingClassInfo.id)) {
             // 기존 수업이 다른 칸에도 남아있을 수 있으므로,
             // 해당 수업의 모든 칸을 찾아보고, 더 이상 존재하지 않으면 학점을 뺍니다.
             let isStillPresent = false;
             for (const d of DAYS) {
                 for (const p of PERIODS) {
                     if (timetableData[d] && timetableData[d][p] && timetableData[d][p].id === existingClassInfo.id && !(d === day && periodsToAdd.includes(parseInt(p)))) {
                         isStillPresent = true;
                         break;
                     }
                 }
                 if (isStillPresent) break;
             }
             if (!isStillPresent) {
                totalCredits -= addedClassCreditsMap.get(existingClassInfo.id);
                addedClassCreditsMap.delete(existingClassInfo.id);
             }
        }
    }

    // 기존에 추가된 수업의 학점 미리 제거 (같은 수업을 다른 곳으로 옮겨 등록하는 경우)
    if (addedClassCreditsMap.has(currentSelectedClass.id)) {
        totalCredits -= addedClassCreditsMap.get(currentSelectedClass.id);
        addedClassCreditsMap.delete(currentSelectedClass.id);
    }


    // 授業の情報をtimetableDataに追加
    for (const p of periodsToAdd) {
        timetableData[day][p] = {
            id: currentSelectedClass.id,
            name: currentSelectedClass.name,
            credit: currentSelectedClass.credit,
            term: currentSelectedClass.term
        };
    }
    
    // 학점은 수업 ID당 한 번만 더합니다.
    if (!addedClassCreditsMap.has(currentSelectedClass.id)) {
        totalCredits += currentSelectedClass.credit;
        addedClassCreditsMap.set(currentSelectedClass.id, currentSelectedClass.credit);
    }
    
    renderTimetable(); // 時間割を再描画

    // 선택된 수업 정보 초기화
    currentSelectedClass = null;
    document.getElementById('currentSelectedClassName').textContent = 'なし';
    document.getElementById('currentSelectedClassCredit').textContent = '0';
    document.getElementById('currentSelectedClassDurationFixed').textContent = '0時限';

    alert(`${currentSelectedClass.name} を ${day}曜日 ${periodsToAdd.join(',')}時限に追加しました。`);
}

// 時間割から授業を削除する関数
function removeClassFromTimetable(day, period) {
    const classInfoInCell = timetableData[day]?.[period];

    if (!classInfoInCell) {
        // 이미 비어있거나, 정보가 없는 셀
        console.warn(`Attempted to remove class from empty or invalid cell: ${day}-${period}`);
        return;
    }

    const classIdToRemove = classInfoInCell.id;

    // 해당 수업 ID를 가진 모든 셀을 timetableData에서 찾아 제거
    let foundAndRemoved = false;
    for (const d of DAYS) {
        if (timetableData[d]) {
            for (const p of PERIODS) {
                if (timetableData[d][p] && timetableData[d][p].id === classIdToRemove) {
                    delete timetableData[d][p];
                    foundAndRemoved = true;
                }
            }
        }
    }

    if (foundAndRemoved) {
        // 해당 수업 ID가 이제 timetableData에 더 이상 존재하지 않는지 최종 확인
        let isClassStillPresent = false;
        for (const d of DAYS) {
            if (timetableData[d]) {
                for (const p of PERIODS) {
                    if (timetableData[d][p] && timetableData[d][p].id === classIdToRemove) {
                        isClassStillPresent = true;
                        break;
                    }
                }
            }
            if (isClassStillPresent) break;
        }

        // 수업이 시간표에서 완전히 제거되었다면 학점 감소
        if (!isClassStillPresent && addedClassCreditsMap.has(classIdToRemove)) {
            totalCredits -= addedClassCreditsMap.get(classIdToRemove);
            addedClassCreditsMap.delete(classIdToRemove);
        }
        
        renderTimetable(); // 시간표를 다시 그리기
        alert('授業が時間割から削除されました。');
    } else {
        alert('削除する授業が見つかりませんでした。'); // 이론적으로 이 메시지는 나오지 않아야 함.
    }
}


// 時間割を描画する関数 (학점 계산 로직 수정 포함)
// 이 함수는 PHP가 이미 기본 시간표 테이블 구조를 생성했다고 가정하고,
// 각 셀의 내용을 업데이트하는 방식으로 동작합니다.
function renderTimetable() {
    // #timetable ID를 가진 테이블의 tbody를 선택합니다.
    const timetableBody = document.querySelector('#timetable tbody');
    if (!timetableBody) {
        console.error("Timetable tbody element with ID 'timetable' not found. Cannot render timetable.");
        return;
    }
    
    // 이전에 헤더를 JS가 그렸지만, 이제 PHP가 그렸다고 가정하고 tbody 내부 셀만 업데이트합니다.
    // 따라서 tbody.innerHTML = ''; 는 제거하고, 기존 셀들을 순회하며 업데이트합니다.
    
    // 학점 계산은 다시 수행합니다.
    totalCredits = 0;
    addedClassCreditsMap = new Map(); // 매번 다시 계산하므로 맵 초기화

    // 각 요일 및 시한에 대한 셀 업데이트
    PERIODS.forEach(period => { // 시한 (행)을 먼저 순회
        DAYS.forEach(day => { // 요일 (열)을 그 다음 순회
            const cellId = `cell-${day}-${period}`;
            const targetCell = document.getElementById(cellId);

            if (targetCell) {
                const classInfo = timetableData?.[day]?.[period]; // 이 셀의 수업 정보

                if (classInfo) {
                    // 수업이 있는 경우
                    targetCell.classList.add('filled-cell', 'filled-primary'); // CSS 클래스 추가
                    // 셀 내용 업데이트 (학점은 셀에 그대로 표시)
                    targetCell.innerHTML = `
                        <div class="class-in-cell">
                            <span class="remove-button" onclick="removeClassFromTimetable('${day}', ${period})">X</span>
                            <div class="class-name-in-cell">${classInfo.name}</div>
                            <div class="class-credit-in-cell">${classInfo.credit}単位</div>
                            <div class="term-display-in-cell">
                                ${getTermName(classInfo.term)}
                            </div>
                        </div>
                    `;

                    // ★ 중요: 학점은 수업 ID당 한 번만 가산
                    if (!addedClassCreditsMap.has(classInfo.id)) {
                        totalCredits += classInfo.credit;
                        addedClassCreditsMap.set(classInfo.id, classInfo.credit);
                    }
                } else {
                    // 수업이 없는 경우 셀을 비웁니다.
                    targetCell.innerHTML = '';
                    targetCell.classList.remove('filled-cell', 'filled-primary');
                }
            } else {
                console.warn(`Timetable cell with ID ${cellId} not found.`);
            }
        });
    });

    // 합계 단위수를 업데이트합니다.
    const totalCreditsElement = document.getElementById('totalCredits');
    if (totalCreditsElement) {
        totalCreditsElement.textContent = `合計単位数: ${totalCredits}`;
    } else {
        console.error("totalCredits element not found.");
    }
}


// 時間割データをサーバーに保存する関数
function saveTimetable() {
    if (!isUserLoggedIn) {
        alert('時間割を保存するにはログインが必要です。');
        return;
    }

    const currentGrade = currentSelectedGradeFromPHP;

    // timetableData 객체에서 저장 형식에 맞는 배열로 변환
    const dataToSave = [];
    for (const day in timetableData) {
        for (const period in timetableData[day]) {
            const classEntry = timetableData[day][period];
            if (classEntry) {
                dataToSave.push({
                    class_id: classEntry.id,
                    day: day,
                    period: parseInt(period),
                    grade: currentGrade
                });
            }
        }
    }

    if (dataToSave.length === 0) {
        alert('保存する授業が時間割にありません。');
        return;
    }
    
    if (!confirm(`現在の時間割 (${totalCredits}単位) を保存しますか？`)) {
        return;
    }
    
    // AJAXリクエストを送信
    fetch('save_timetable.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            userId: currentLoggedInUserId,
            grade: currentGrade,
            timetable: dataToSave
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('時間割が正常に保存されました！');
        } else {
            alert('時間割の保存に失敗しました: ' + data.message);
            console.error('Save failed:', data.message);
        }
    })
    .catch(error => {
        console.error('Error saving timetable:', error);
        alert('時間割の保存中にエラーが発生しました。');
    });
}