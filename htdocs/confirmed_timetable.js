document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. 전역 변수 초기화 및 로그인 사용자 ID 설정
    let currentUserId = null;
    const bodyElement = document.body;
    const userIdFromDataAttribute = bodyElement.dataset.userId;

    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10);
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }

    if (currentUserId === null) {
        const messageContainer = document.getElementById('confirmed-timetable-message');
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ログインしていません。ログイン後に確定済み時間割を確認できます。</p>';
        }
        console.error("ユーザーIDが設定されていません。確定済み時間割をロードできません。");
        return;
    }

    console.log("DEBUG: confirmed_timetable.js - currentUserId:", currentUserId, "type:", typeof currentUserId);
    // =========================================================

    // =========================================================
    // 2. DOM 요소 선택
    const confirmedTimetableTable = document.getElementById('confirmed-timetable-table');
    const timetableTableBody = confirmedTimetableTable ? confirmedTimetableTable.querySelector('tbody') : null;
    const messageContainer = document.getElementById('confirmed-timetable-message');
    const confirmedTimetableGradeSelect = document.getElementById('confirmedTimetableGradeSelect');
    const confirmedTimetableTermSelect = document.getElementById('confirmedTimetableTermSelect');

    if (currentUserId === null) {
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ユーザーIDが設定されていません。確定済み時間割をロードできません。</p>';
        }
        return;
    }

    /**
     * 시간표 가져오기 및 표시
     */
    function fetchConfirmedTimetable() {
        const targetGrade = confirmedTimetableGradeSelect ? confirmedTimetableGradeSelect.value : '1';
        const targetTerm = confirmedTimetableTermSelect ? confirmedTimetableTermSelect.value : '前期';

        if (!timetableTableBody) {
            console.error("エラー: 時間割テーブルのtbody要素が見つかりません。");
            if (messageContainer) {
                messageContainer.innerHTML = '<p class="error-message">時間割テーブルの表示に問題があります。</p>';
            }
            return;
        }

        // 既存の時間割セルのクリア
        timetableTableBody.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            cell.innerHTML = '';
            cell.classList.remove('filled-primary');
        });

        if (messageContainer) {
            messageContainer.innerHTML = '';
        }

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
                        return;
                    }

                    data.timetable.forEach(entry => {
                        // entry.day と entry.periodの値をデバッグ出力
                        console.log(`探すセル: day=${entry.day}, period=${entry.period}`);

                        // ここはHTML構造に合わせて変更してください
                        // 例: data-dayとdata-periodが数字なら問題なし
                        const cellSelector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTableBody.querySelector(cellSelector);

                        if (!targetCell) {
                            console.warn(`時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period}`);
                            return; // 見つからなければスキップ
                        }

                        const className = entry.class_name || '不明な授業';
                        const classCredit = entry.class_credit || '?';
                        const classOriginalGrade = entry.class_original_grade || '';
                        const classCategory1 = entry.category1 || '';
                        const classCategory2 = entry.category2 || '';

                        targetCell.innerHTML = `
                            <span class="class-name-in-cell">${className}</span><br>
                            <span class="class-credit-in-cell">${classCredit}単位</span><br>
                            <span class="category-display-in-cell">${classOriginalGrade}年 / ${classCategory1} / ${classCategory2}</span>
                        `;
                        targetCell.classList.add('filled-primary');
                    });

                    if (messageContainer) {
                        messageContainer.innerHTML = `<p class="success-message">確定済み時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。</p>`;
                    }
                } else {
                    if (messageContainer) {
                        messageContainer.innerHTML = `<p class="error-message">確定済み時間割のロードに失敗しました: ${data.message}</p>`;
                    }
                    console.error('確定済み時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                if (messageContainer) {
                    messageContainer.innerHTML = '<p class="error-message">確定済み時間割の読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
                }
                console.error('確定済み時間割のロード中にエラーが発生しました:', error);
            });
    }

    // 学年・学期変更時のイベント登録
    if (confirmedTimetableGradeSelect) {
        confirmedTimetableGradeSelect.addEventListener('change', fetchConfirmedTimetable);
    }
    if (confirmedTimetableTermSelect) {
        confirmedTimetableTermSelect.addEventListener('change', fetchConfirmedTimetable);
    }

    // 初期ロード時に時間割を取得表示
    fetchConfirmedTimetable();
});
