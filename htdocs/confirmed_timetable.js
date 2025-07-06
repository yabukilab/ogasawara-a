document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. 전역 (이 스크립트 파일 내) 변수 초기화 및 로그인 사용자 ID 설정
    //    body 태그의 data-user-id 속성에서 사용자 ID를 읽어옵니다.
    // =========================================================
    let currentUserId = null;
    const bodyElement = document.body;
    const userIdFromDataAttribute = bodyElement.dataset.userId;

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10);
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }

    // ユーザーIDが取得できない場合は、処理を中断しエラーメッセージを表示
    const messageContainer = document.getElementById('confirmed-timetable-message');
    if (currentUserId === null) {
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ログインしていません。ログイン後に確定済み時間割を確認できます。</p>';
        }
        console.error("ユーザーIDが設定されていません。確定済み時間割をロードできません。");
        // ログインページへのリダイレクトが必要な場合は追加
        // window.location.href = 'login.php';
        return; // 로그인하지 않았으면 이후 로직 실행 중단
    }

    console.log("DEBUG: confirmed_timetable.js - currentUserIdの最終値:", currentUserId, "タイプ:", typeof currentUserId);
    // =========================================================

    // =========================================================
    // 2. DOM 요소 선택
    // =========================================================
    const confirmedTimetableTable = document.getElementById('confirmed-timetable-table'); // 테이블 전체
    const timetableTableBody = confirmedTimetableTable ? confirmedTimetableTable.querySelector('tbody') : null; // tbody를 명시적으로 선택

    // 확정 시간표 페이지의 학년 및 학기 선택 드롭다운
    const confirmedTimetableGradeSelect = document.getElementById('confirmedTimetableGradeSelect');
    const confirmedTimetableTermSelect = document.getElementById('confirmedTimetableTermSelect');

    /**
     * 서버로부터 사용자의 확정된 시간표를 가져와 표시합니다.
     */
    function fetchConfirmedTimetable() {
        // 현재 선택된 학년과 학기 값을 가져옵니다.
        const targetGrade = confirmedTimetableGradeSelect ? confirmedTimetableGradeSelect.value : '1'; // 기본값 1학년
        const targetTerm = confirmedTimetableTermSelect ? confirmedTimetableTermSelect.value : '前期'; // 기본값 전기

        if (!timetableTableBody) {
            console.error("エラー: 時間割テーブルのtbody要素が見つかりません。");
            if (messageContainer) {
                messageContainer.innerHTML = '<p class="error-message">時間割テーブルの表示に問題があります。</p>';
            }
            return;
        }

        // 시간표 표시를 위해 기존 내용을 모두 지웁니다.
        timetableTableBody.querySelectorAll('.time-slot').forEach(cell => { // filled-primary 클래스 제거 조건 삭제
            cell.innerHTML = '';
            cell.classList.remove('filled-primary'); // 이전 데이터의 클래스 제거
            cell.style.backgroundColor = ''; // 배경색 초기화
        });
        // 메시지 컨테이너 초기화
        if (messageContainer) {
            messageContainer.innerHTML = '';
        }

        // get_timetable.php에 AJAX 요청을 보내 저장된 시간표를 가져옵니다.
        // 학년과 학기 정보를 쿼리 파라미터로 추가합니다.
        fetch(`get_timetable.php?user_id=${currentUserId}&grade=${targetGrade}&term=${targetTerm}`) // 쿼리 파라미터 이름 수정
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    if (data.timetable.length === 0) {
                        if (messageContainer) {
                            messageContainer.innerHTML = '<p class="info-message">選択された学年・学期の確定済み時間割がありません。</p>';
                        }
                    } else {
                        // 가져온 데이터로 시간표 테이블을 채웁니다.
                        data.timetable.forEach(entry => {
                            // get_timetable.php는 'day' 컬럼 이름을 그대로 반환합니다.
                            // 만약 DB에 일본어 요일이 저장되어 있고, get_timetable.php가 이를 반환한다면,
                            // 아래 dayMap을 사용하여 영어 요일로 변환해야 합니다.
                            // const dayMap = {'月曜日': 'Monday', '火曜日': 'Tuesday', /* ... */};
                            // const day = dayMap[entry.day]; // entry.day가 일본어 요일일 경우
                            const day = entry.day; // entry.day가 영어 요일일 경우 (현재 HTML data-day와 일치하도록)
                            const period = entry.period;

                            const cellSelector = `.time-slot[data-day="${day}"][data-period="${period}"]`;
                            const targetCell = timetableTableBody.querySelector(cellSelector);

                            if (targetCell) {
                                const className = entry.class_name || '不明な授業';
                                const classCredit = entry.credit || '?'; // 'credit'으로 필드명 수정
                                // get_timetable.php에서 'class_original_grade'로 별칭을 지정했습니다.
                                const classOriginalGrade = entry.class_original_grade || '';
                                const classCategory1 = entry.category1 || '';
                                const classCategory2 = entry.category2 || '';

                                targetCell.innerHTML = `
                                    <span class="class-name-in-cell">${className}</span><br>
                                    <span class="class-credit-in-cell">${classCredit}単位</span><br>
                                    <span class="category-display-in-cell">${classOriginalGrade}年 / ${classCategory1} / ${classCategory2}</span>
                                    <button class="remove-lesson-btn" data-class-id="${entry.class_id}"
                                            data-day="${day}" data-period="${period}"
                                            data-grade="${entry.timetable_grade}" data-term="${entry.timetable_term}">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                `;
                                targetCell.classList.add('filled-primary');
                                // 수업 유형에 따른 배경색 설정 (CSS로 관리하는 것이 더 좋음)
                                if (classCategory1 === '専門科目') {
                                    targetCell.style.backgroundColor = '#e0ffe0';
                                } else if (classCategory1 === '一般教養科目') {
                                    targetCell.style.backgroundColor = '#e0f0ff';
                                } else if (classCategory1 === '基礎科目') {
                                    targetCell.style.backgroundColor = '#fffacd';
                                }
                            } else {
                                console.warn(`時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period}`);
                            }
                        });
                        if (messageContainer) {
                            messageContainer.innerHTML = `<p class="success-message">確定済み時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。</p>`;
                        }
                    }
                } else {
                    // 서버에서 오류 응답을 보낸 경우
                    if (messageContainer) {
                        messageContainer.innerHTML = `<p class="error-message">確定済み時間割のロードに失敗しました: ${data.message}</p>`;
                    }
                    console.error('確定済み時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                // 네트워크 또는 파싱 오류 발생 시
                if (messageContainer) {
                    messageContainer.innerHTML = '<p class="error-message">確定済み時間割の読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
                }
                console.error('確定済み時間割のロード中にエラーが発生しました:', error);
            });
    }

    // =========================================================
    // 3. イベント 리스너 등록 및 초기 실행
    // =========================================================

    // 학년 또는 학기 선택 변경 시 이벤트 리스너 (DOM 요소가 존재할 경우에만 등록)
    if (confirmedTimetableGradeSelect) {
        confirmedTimetableGradeSelect.addEventListener('change', fetchConfirmedTimetable);
    }
    if (confirmedTimetableTermSelect) {
        confirmedTimetableTermSelect.addEventListener('change', fetchConfirmedTimetable);
    }

    // 페이지 로드 시 확정 시간표를 가져와 표시하는 함수를 호출합니다.
    fetchConfirmedTimetable();

    // =========================================================
    // 4. 수업 삭제 버튼 이벤트 리스너 (confirmed_timetable.php에서 삭제 기능을 사용한다면)
    // =========================================================
    if (confirmedTimetableTable) { // 테이블이 존재하는지 확인
        confirmedTimetableTable.addEventListener('click', async (event) => {
            // 클릭된 요소가 .remove-lesson-btn이거나 그 자손인지 확인
            const removeButton = event.target.closest('.remove-lesson-btn');
            if (removeButton) {
                const classId = removeButton.dataset.classId;
                const day = removeButton.dataset.day; // data-day 속성 사용
                const period = removeButton.dataset.period;
                const grade = removeButton.dataset.grade;
                const term = removeButton.dataset.term;

                if (!confirm('この授業を時間割から削除しますか？')) {
                    return;
                }

                try {
                    const response = await fetch('delete_timetable_entry.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `user_id=${currentUserId}&class_id=${classId}&day=${day}&period=${period}&grade=${grade}&term=${term}`
                    });

                    const data = await response.json();
                    if (data.status === 'success') {
                        alert('授業が時間割から削除されました。');
                        fetchConfirmedTimetable(); // 시간표 새로고침
                    } else {
                        alert('授業の削除に失敗しました: ' + data.message);
                    }
                } catch (error) {
                    console.error('授業削除中にエラー:', error);
                    alert('授業削除中にエラーが発生しました。');
                }
            }
        });
    }
}); // DOMContentLoaded 閉じ括弧