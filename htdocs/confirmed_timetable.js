document.addEventListener('DOMContentLoaded', function() {
    const timetableTableBody = document.getElementById('confirmed-timetable-body');
    const messageContainer = document.getElementById('confirmed-timetable-message');

    let currentUserId = null; 

    // PHP에서 넘어온 사용자 ID를 전역 변수로 가져옵니다. (confirmed_timetable.php에서 설정해야 함)
    // 예: <script>const currentUserIdFromPHP = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;</script>
    // ユーザーIDの取得処理を関数にまとめる
function initializeUserId() {
    if (typeof window.currentUserIdFromPHP !== 'undefined' && window.currentUserIdFromPHP !== null) {
        currentUserId = window.currentUserIdFromPHP;
    } else {
        // 로그인되지 않은 경우 메시지를 표시하고 종료합니다.
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ログインしていません。ログイン後に確定済み時間割を確認できます。</p>';
        }
        console.warn("警告: currentUserIdFromPHPが定義されていないかnullです。確定済み時間割をロードできません。");
        return falce; 
    }
}
    /**
     * 서버로부터 사용자의 확정된 시간표를 가져와 표시합니다.
     */
    function fetchConfirmedTimetable() {
        if (currentUserId === null) {
            console.error("ユーザーIDが設定されていません。確定済み時間割をロードできません。");
            return;
        }

        // get_timetable.php에 AJAX 요청을 보내 저장된 시간표를 가져옵니다.
        // 이 파일은 사용자 시간표 데이터를 가져오기 위해 이미 수정된 get_timetable.php를 재사용합니다.
        fetch(`get_timetable.php?user_id=${currentUserId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // 시간표 표시를 위해 기존 내용을 모두 지웁니다.
                    // confirmed_timetable.php에 data-day 및 data-period 속성을 가진 셀이 있다고 가정합니다.
                    timetableTableBody.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                        cell.innerHTML = '';
                        cell.classList.remove('filled-primary');
                    });


                    if (data.timetable.length === 0) {
                        if (messageContainer) {
                            messageContainer.innerHTML = '<p class="info-message">まだ確定された時間割がありません。</p>';
                        }
                    } else {
                        // 가져온 데이터로 시간표 테이블을 채웁니다.
                        data.timetable.forEach(entry => {
                            // data-day 및 data-period 속성을 사용하여 올바른 시간 슬롯을 선택합니다.
                            // 참고: get_timetable.php는 요일에 대해 'day_of_week'를 반환합니다.
                            const cellSelector = `.time-slot[data-day="${entry.day_of_week}"][data-period="${entry.period}"]`;
                            const targetCell = timetableTableBody.querySelector(cellSelector);

                            if (targetCell) {
                                // get_timetable.php는 class_name, class_credit, grade, category1, category2, category3를 반환합니다.
                                const className = entry.class_name || '不明な授業';
                                const classCredit = entry.class_credit || '?';
                                const classGrade = entry.grade || ''; // user_timetables의 grade
                                const classCategory1 = entry.category1 || ''; 
                                const classCategory2 = entry.category2 || '';

                                targetCell.innerHTML = `
                                    <span class="class-name-in-cell">${className}</span>
                                    <span class="class-credit-in-cell">${classCredit}単位</span>
                                    <span class="category-display-in-cell">${classGrade}年 / ${classCategory1} / ${classCategory2}</span>
                                `;
                                targetCell.classList.add('filled-primary');
                            }
                        });
                        if (messageContainer) {
                            messageContainer.innerHTML = '<p class="success-message">確定済み時間割が正常にロードされました。</p>';
                        }
                    }
                    console.log('確定済み時間割が正常にロードされました。', data);

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

    // 페이지 로드 시 시간표를 가져와 표시하는 함수를 호출합니다.
    fetchConfirmedTimetable();
});