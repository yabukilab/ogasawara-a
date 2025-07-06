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
    if (currentUserId === null) {
        const messageContainer = document.getElementById('confirmed-timetable-message');
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
    const messageContainer = document.getElementById('confirmed-timetable-message');

    // 확정 시간표 페이지의 학년 및 학期 선택 드롭다운
    const confirmedTimetableGradeSelect = document.getElementById('confirmedTimetableGradeSelect');
    const confirmedTimetableTermSelect = document.getElementById('confirmedTimetableTermSelect');


    // ユーザーIDが取得できない場合は、処理を中断
    if (currentUserId === null) {
        // 이 부분은 위에 이미 처리했지만, 혹시 몰라 다시 한번 확인합니다.
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ユーザーIDが設定されていません。確定済み時間割をロードできません。</p>';
        }
        return;
    }

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
        timetableTableBody.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        });
        // 메시지 컨테이너 초기화
        if (messageContainer) {
            messageContainer.innerHTML = '';
        }

        // get_timetable.php에 AJAX 요청을 보내 저장된 시간표를 가져옵니다.
        // 학년과 학기 정보를 쿼리 파라미터로 추가합니다.
        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`)
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
                            const cellSelector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                            const targetCell = timetableTableBody.querySelector(cellSelector);

                            if (targetCell) {
                                const className = entry.class_name || '不明な授業';
                                const classCredit = entry.class_credit || '?';
                                // get_timetable.php에서 'class_original_grade'로 별칭을 지정했습니다.
                                const classOriginalGrade = entry.class_original_grade || '';
                                const classCategory1 = entry.category1 || '';
                                const classCategory2 = entry.category2 || '';

                                targetCell.innerHTML = `
                                    <span class="class-name-in-cell">${className}</span>
                                    <span class="class-credit-in-cell">${classCredit}単位</span>
                                    <span class="category-display-in-cell">${classOriginalGrade}年 / ${classCategory1} / ${classCategory2}</span>
                                `;
                                targetCell.classList.add('filled-primary');
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
    // 3. 이벤트 리스너 등록 및 초기 실행
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
}); // DOMContentLoaded 閉じ括弧