document.addEventListener('DOMContentLoaded', function() {
    // =========================================================
    // 1. グローバル (このスクリプトファイル内) 変数初期化およびログインユーザーID設定
    //    body タグの data-user-id 属性からユーザーIDを読み込みます。
    // =========================================================
    let currentUserId = null;
    const bodyElement = document.body; // body 要素参照
    const userIdFromDataAttribute = bodyElement.dataset.userId; // data-user-id 属性値取得

    // userIdFromDataAttributeは文字列 "null" または実際のID数字文字列 ("5") になります。
    if (userIdFromDataAttribute !== 'null' && userIdFromDataAttribute !== undefined) {
        currentUserId = parseInt(userIdFromDataAttribute, 10); // 数値に変換
    } else {
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }

    console.log("DEBUG: main_script.js - currentUserIdの最終値:", currentUserId, "タイプ:", typeof currentUserId);
    // =========================================================

    // =========================================================
    // 2. DOM 要素選択
    //    すべてのDOM要素選択は、この 'DOMContentLoaded' ブロック内で行われるべきです。
    // =========================================================
    const classFilterForm = document.getElementById('classFilterForm');
    // 授業リストフィルターの学年と学期セレクタ
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    
    const classListContainer = document.getElementById('lesson-list-container');

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    // 時間割表示用の学年選択ドロップダウン
    const timetableGradeSelect = document.getElementById('timetableGradeSelect');
    // 時間割表示用の学期選択ドロップダウン
    const timetableTermSelect = document.getElementById('timetableTermSelect');

    // --- デバッグ: timetableGradeSelectとtimetableTermSelectの初期値をログ出力 ---
    console.log("DEBUG: DOM elements - timetableGradeSelect:", timetableGradeSelect, "timetableTermSelect:", timetableTermSelect);
    if (timetableGradeSelect) {
        console.log("DEBUG: timetableGradeSelect.value on load:", JSON.stringify(timetableGradeSelect.value)); // JSON.stringifyで厳密な値を確認
    }
    if (timetableTermSelect) {
        console.log("DEBUG: timetableTermSelect.value on load:", JSON.stringify(timetableTermSelect.value)); // JSON.stringifyで厳密な値を確認
    }
    // --- デバッグ終わり ---

    // --- 修正: 明示的にデフォルト値を設定 ---
    // HTMLのselected属性が正しく機能しない場合に備え、JavaScriptでデフォルト値を保証
    if (timetableGradeSelect && !timetableGradeSelect.value) {
        timetableGradeSelect.value = '1'; // デフォルトで1年生に設定
        console.warn("警告: timetableGradeSelectの初期値が空だったため、'1'に設定しました。");
    }
    if (timetableTermSelect && !timetableTermSelect.value) {
        timetableTermSelect.value = '前期'; // デフォルトで前期に設定
        console.warn("警告: timetableTermSelectの初期値が空だったため、'前期'に設定しました。");
    }
    // --- 修正終わり ---

    let draggedClass = null; // ドラッグ中の授業データを保存する変数

    // --- 追加: 総単位管理変数および表示DOM要素 ---
    let totalCredit = 0;
    const currentTotalCreditSpan = document.getElementById('current-total-credit'); // 追加されたHTML要素
    // --- 追加終わり ---

    // =========================================================
    // 3. 関数定義
    // =========================================================

    // --- 3.1. 授業リストのフィルタリングと読み込み ---
    function fetchAndDisplayClasses() {
        // nullチェックを追加
        if (!gradeSelect || !termSelect) {
            console.error("エラー: 'gradeFilter' または 'termFilter' の要素が見つかりません。HTMLを確認してください。");
            if (classListContainer) {
                classListContainer.innerHTML = '<p class="message error">授業のフィルターに必要な要素が見つかりません。</p>';
            }
            return; // 要素が見つからない場合、処理を中断
        }

        const grade = gradeSelect.value;
        const term = termSelect.value;

        fetch(`show_lessons.php?grade=${grade}&term=${term}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (classListContainer) { // classListContainerが存在するか確認
                    classListContainer.innerHTML = ''; // コンテンツをクリア
                }

                if (data.status === 'success') {
                    const classes = data.lessons;

                    if (classes.length === 0) {
                        if (classListContainer) {
                            classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                        }
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
                        classItem.dataset.category1 = cls.category1; // classテーブルから読み込むがフィルタリングには使用しない
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3;

                        classItem.innerHTML = `
                            <div class="lesson-name">${cls.name}</div>
                            <div class="lesson-details">
                                <span class="lesson-credit">${cls.credit}単位</span>
                            </div>
                        `;
                        if (classListContainer) { // classListContainerが存在するか確認
                            classListContainer.appendChild(classItem);
                        }
                    });
                    addDragListeners();
                } else {
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    if (classListContainer) {
                        classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                    }
                }
            })
            .catch(error => {
                console.error('授業データの取得中にネットワークエラーが発生しました:', error);
                if (classListContainer) {
                    classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。</p>';
                }
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
        if (!timetableTable) { // timetableTableが存在しない場合は処理しない
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

                // =====================================================================
                // ***** 1つのセルに1つの授業のみドロップされるように強制するロジック追加/修正 *****
                // このセルに既に .class-item-in-cell クラスを持つ子要素があるか確認
                if (this.querySelector('.class-item-in-cell')) {
                    alert('この時間枠にはすでに授業があります。新しい授業を追加する前に、既存の授業を削除してください。');
                    return; // 既に授業があれば追加せずに関数終了
                }
                // =====================================================================

                const classId = draggedClass.dataset.id;
                const className = draggedClass.dataset.name;
                const classCredit = parseInt(draggedClass.dataset.credit, 10); // 単位を数値に変換
                const classGrade = draggedClass.dataset.grade;
                // const classCategory2 = draggedClass.dataset.category2; // 必要であれば使用

                // 新しい授業アイテム要素を作成
                const classItemInCell = document.createElement('div');
                classItemInCell.classList.add('class-item-in-cell');
                classItemInCell.setAttribute('draggable', true); // セル内のアイテムもドラッグ可能にする場合

                // 重要: セルの data-day と data-period を直接読み取り、classItemInCellに保存
                // これにより、後で saveTimetable 関数でこの値を正確に読み取ることができます。
                classItemInCell.dataset.classId = classId;
                classItemInCell.dataset.day = this.dataset.day;    // <-- セルの data-day 値を取得
                classItemInCell.dataset.period = this.dataset.period; // <-- セルの data-period 値を取得

                classItemInCell.innerHTML = `
                    <span class="class-name-in-cell">${className}</span>
                    <span class="class-credit-in-cell">${classCredit}単位</span>
                    <span class="category-display-in-cell">${classGrade}年</span>
                    <button class="remove-button">&times;</button>
                `;
                
                // 既存の innerHTML = ... の代わりに appendChild を使用
                this.appendChild(classItemInCell);
                this.classList.add('filled-primary');

                // 削除ボタンにイベントリスナーを追加 (新しく生成されたボタンに接続)
                classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                // --- 総単位に現在の授業の単位を足して表示 ---
                totalCredit += classCredit;
                updateAndDisplayTotalCredit();
                // --- 追加終わり ---
            });
        });
    }

    // --- 3.4. 時間割から授業削除 ---
    function removeClassFromTimetable(event) {
        const classItemInCell = event.target.closest('.class-item-in-cell'); // .class-item-in-cell を探す
        const cell = event.target.closest('.time-slot'); // 親の .time-slot を探す

        if (classItemInCell && cell) {
            const removedCreditSpan = classItemInCell.querySelector('.class-credit-in-cell'); // .class-item-in-cell 内で探す
            if (removedCreditSpan) {
                const removedCreditText = removedCreditSpan.textContent;
                const removedCredit = parseInt(removedCreditText.replace('単位', ''), 10);
                if (!isNaN(removedCredit)) {
                    totalCredit -= removedCredit;
                    updateAndDisplayTotalCredit();
                }
            }
            // classItemInCell 自体を削除
            classItemInCell.remove();
            // セルが空になったか確認し、filled-primary クラスを削除
            if (!cell.querySelector('.class-item-in-cell')) {
                cell.classList.remove('filled-primary');
            }
        }
    }

    // --- 3.5. 時間割保存機能 (timetable_grade, timetable_term 追加) ---
    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。ログイン後に時間割を保存できます。');
            window.location.href = 'login.php';
            return;
        }

        // nullチェックを追加
        if (!timetableGradeSelect || !timetableTermSelect) {
            alert('時間割の保存に失敗しました: 時間割表示用の学年または学期セレクタが見つかりません。');
            console.error("エラー: 'timetableGradeSelect' または 'timetableTermSelect' の要素が見つかりません。HTMLを確認してください。");
            return;
        }

        const selectedTimetableGrade = timetableGradeSelect.value; // 現在選択されている学年を取得
        const selectedTimetableTerm = timetableTermSelect.value;   // 新しく追加された学期を取得

        const timetableData = [];
        // .filled-primary セルではなく、その中にある .class-item-in-cell をすべて探します。
        if (timetableTable) { // timetableTableが存在するか確認
            timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
                const classId = itemInCell.dataset.classId;
                const day = itemInCell.dataset.day;    // <-- class-item-in-cellに保存された data-day を読み込む
                const period = itemInCell.dataset.period; // <-- class-item-in-cellに保存された data-period を読み込む

                timetableData.push({
                    class_id: classId,
                    day_of_week: day, // この値が save_timetable.php に渡される
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
                timetable_term: selectedTimetableTerm, // <-- 学期情報追加
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
                // 保存後、現在選択されている学年/学期の時間割を再度ロード
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

    // --- 3.6. 保存された時間割の読み込み (timetable_grade, timetable_term 追加) ---
    function loadTimetable() { // パラメータを削除、内部で直接現在選択されている値を使用
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        // nullチェックを追加
        if (!timetableGradeSelect || !timetableTermSelect) {
            console.warn("時間割のロードに失敗: 時間割表示用の学年または学期セレクタが見つかりません。");
            return;
        }

        const targetGrade = timetableGradeSelect.value;
        const targetTerm = timetableTermSelect.value; // 現在選択されている学期を使用
        
        // --- デバッグ: loadTimetable内の値をログ出力 ---
        console.log("DEBUG: loadTimetable - targetGrade:", JSON.stringify(targetGrade), "targetTerm:", JSON.stringify(targetTerm));
        // --- デバッグ終わり ---

        // --- 追加デバッグ: targetGradeとtargetTermの真偽値評価を確認 ---
        console.log("DEBUG: loadTimetable - targetGrade is falsy?", !targetGrade, "targetTerm is falsy?", !targetTerm);
        console.log("DEBUG: loadTimetable - Condition (targetGrade === '' || targetTerm === '') is:", (targetGrade === "" || targetTerm === "")); // 新しいデバッグログ
        // --- 追加デバッグ終わり ---

        // 厳密な空文字列チェックに変更
        if (targetGrade === "" || targetTerm === "") {
            console.warn("時間割のロードに失敗: 学年と学期を選択してください (空文字列検出 - 警告ID: LT-101)"); // 警告IDを追加
            return;
        }
        // --- 修正: 時間割ロード前に総単位を初期化 ---
        totalCredit = 0; // ロード前に初期化
        updateAndDisplayTotalCredit(); // 初期化された値をすぐに反映
        // --- 修正終わり ---

        // 既存の時間割を初期化: すべての .class-item-in-cell 要素を削除し、.filled-primary クラスを削除
        if (timetableTable) { // timetableTableが存在するか確認
            timetableTable.querySelectorAll('.class-item-in-cell').forEach(itemInCell => {
                itemInCell.remove();
            });
            timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                cell.classList.remove('filled-primary');
            });
        }

        fetch(`get_timetable.php?user_id=${currentUserId}&timetable_grade=${targetGrade}&timetable_term=${targetTerm}`) // <-- 学年および学期情報を追加
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    data.timetable.forEach(entry => {
                        // DBから取得した entry.day (日本語曜日) とHTMLセルの data-day (日本語曜日) をマッチング
                        const cellSelector = `.time-slot[data-day="${entry.day}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable ? timetableTable.querySelector(cellSelector) : null; // timetableTableが存在するか確認

                        if (targetCell) {
                            const className = entry.class_name || '不明な授業';
                            const classCredit = parseInt(entry.class_credit, 10) || 0; // 単位を数値に変換
                            const classOriginalGrade = entry.class_original_grade || ''; // 授業の実際の学年

                            // 新しい class-item-in-cell 要素を作成して追加
                            const classItemInCell = document.createElement('div');
                            classItemInCell.classList.add('class-item-in-cell');
                            // ロード時にも data-day と data-period を class-item-in-cell に保存して一貫性を維持
                            classItemInCell.dataset.classId = entry.class_id;
                            classItemInCell.dataset.day = entry.day;    // DBから取得した曜日を保存
                            classItemInCell.dataset.period = entry.period; // DBから取得した時限を保存

                            classItemInCell.innerHTML = `
                                <span class="class-name-in-cell">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classOriginalGrade}年</span>
                                <button class="remove-button">&times;</button>
                            `;
                            targetCell.appendChild(classItemInCell);
                            targetCell.classList.add('filled-primary');

                            classItemInCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);

                            // --- 追加: ロードされた授業の単位を総単位に足す ---
                            totalCredit += classCredit;
                            // --- 追加終わり ---
                        } else {
                            console.warn(`時間割セルが見つかりませんでした: Day ${entry.day}, Period ${entry.period}`);
                        }
                    });
                    // --- 追加: すべての授業ロード後、総単位を最終更新 ---
                    updateAndDisplayTotalCredit();
                    // --- 追加終わり ---
                    console.log(`時間割 (学年: ${targetGrade}, 学期: ${targetTerm}) が正常にロードされました。`);
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
                // alert('時間割のロード中にエラーが発生しました。ネットワーク接続を確認してください。'); // 頻繁に表示されないように警告の代わりにコンソール出力
            });
    }

    // --- ヘルパー関数: 総単位の更新と表示 ---
    function updateAndDisplayTotalCredit() {
        if (currentTotalCreditSpan) {
            currentTotalCreditSpan.textContent = totalCredit;
        }
    }
    // --- ヘルパー関数終わり ---

    // =========================================================
    // 4. イベントリスナー登録と初期実行
    // =========================================================

    // フィルターフォーム送信イベント
    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault();
            fetchAndDisplayClasses();
        });
    } else {
        console.warn("警告: 'classFilterForm' の要素が見つかりません。フィルター機能が動作しません。");
    }

    // ページロード時に授業リストを一度ロードします。
    // gradeSelectとtermSelectが正常に取得できた場合のみ実行
    if (gradeSelect && termSelect) {
        fetchAndDisplayClasses();
    } else {
        console.warn("警告: 初期授業表示に必要なフィルター要素が見つからないため、授業リストの自動ロードは行われません。");
    }

    // ドロップリスナーはページロード時に一度だけ追加すればよいです。(セルは固定されているため)
    addDropListeners();

    // 時間割保存ボタンクリックイベント
    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    } else {
        console.warn("警告: 'saveTimetableBtn' の要素が見つかりません。時間割保存機能が動作しません。");
    }

    // 学年または学期選択変更時のイベントリスナー
    if (timetableGradeSelect) {
        timetableGradeSelect.addEventListener('change', loadTimetable); // 学年変更時に時間割をロード
    }
    if (timetableTermSelect) {
        timetableTermSelect.addEventListener('change', loadTimetable); // 学期変更時に時間割をロード
    }

    // ログイン済みユーザーの場合、保存された時間割を自動的にロードします。
    // ページロード時に初期学年/学期 (デフォルトで1年生/前期) の時間割をロード
    if (currentUserId !== null) {
        // timetableGradeSelectとtimetableTermSelectが正常に取得できた場合のみ実行
        if (timetableGradeSelect && timetableTermSelect) {
            loadTimetable(); // loadTimetableは引数を受け取らず、内部で現在選択されている値を取得します。
        } else {
            console.warn("警告: ログインユーザーの時間割自動ロードに必要な時間割選択要素が見つかりません。");
        }
    } else {
        console.log("ユーザーがログインしていません。時間割の自動ロードは行われません。");
    }

    // 初期ロード時に総単位を表示 (必要に応じて)
    // loadTimetable() が既に呼び出されるので、その中で初期化と表示が行われるはずです。
    // もしログインしていない状態で空の時間割が最初に表示される場合、ここで再度呼び出すことができます。
    updateAndDisplayTotalCredit();

}); // DOMContentLoaded 閉じ括弧
