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
        // 이 경고 메시지가 main_script.js:20에 해당할 것입니다.
        console.warn("警告: currentUserIdFromPHPが定義されていません。ゲストモードで動作します。(via data attribute)");
    }
    // =========================================================


    // =========================================================
    // 2. DOM 요소 선택
    //    모든 DOM 요소 선택은 이 'DOMContentLoaded' 블록 안에서 이루어져야 합니다.
    // =========================================================
    const classFilterForm = document.getElementById('classFilterForm');
    const gradeSelect = document.getElementById('gradeFilter');
    const termSelect = document.getElementById('termFilter');
    const facultySelect = document.getElementById('facultyFilter'); 

    // 수업 목록 컨테이너 ID를 'lesson-list-container'로 변경되었음을 전제로 합니다.
    const classListContainer = document.getElementById('lesson-list-container'); 

    const timetableTable = document.getElementById('timetable-table');
    const saveTimetableButton = document.getElementById('saveTimetableBtn');

    let draggedClass = null; // 드래그 중인 수업 데이터를 저장할 변수

    // =========================================================
    // 3. 함수 정의
    // =========================================================

    // --- 3.1. 수업 목록 필터링 및 불러오기 ---
    function fetchAndDisplayClasses() {
        // 필터 값 가져오기
        const grade = gradeSelect.value;
        const term = termSelect.value;
        const faculty = facultySelect ? facultySelect.value : ''; // facultySelect가 없을 경우를 대비

        // show_lessons.php로부터 데이터를 비동기로 가져옴
        fetch(`show_lessons.php?grade=${grade}&term=${term}&faculty=${faculty}`)
            .then(response => {
                // HTTP 응답이 정상인지 확인
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json(); // JSON 형식으로 파싱
            })
            .then(data => { 
                classListContainer.innerHTML = ''; // 기존 수업 목록을 비웁니다.
                
                if (data.status === 'success') {
                    const classes = data.lessons; // 실제 수업 데이터는 data.lessons 안에 있습니다.

                    if (classes.length === 0) {
                        classListContainer.innerHTML = '<p>該当する授業が見つかりません。</p>';
                        return;
                    }

                    // 각 수업 항목을 생성하여 컨테이너에 추가
                    classes.forEach(cls => {
                        const classItem = document.createElement('div');
                        classItem.classList.add('class-item', 'draggable'); 
                        classItem.setAttribute('draggable', true);
                        
                        // データセット属性割り当て: JSON応答のキーと正確に一致させます。
                        // これらのデータはドラッグアンドドロップ時に時間割セルに情報を表示するのに使われます。
                        classItem.dataset.id = cls.id;
                        classItem.dataset.name = cls.name;
                        classItem.dataset.credit = cls.credit;
                        classItem.dataset.grade = cls.grade;
                        classItem.dataset.category1 = cls.category1;
                        classItem.dataset.category2 = cls.category2;
                        classItem.dataset.category3 = cls.category3; // 必要時追加

                        // 授業項目HTML構造 (index.phpのCSSに合わせて調整)
                        classItem.innerHTML = `
                            <div class="lesson-name">${cls.name}</div>
                            <div class="lesson-details">
                                <span class="lesson-credit">${cls.credit}単位</span>
                                <span class="lesson-category">${cls.category1} (${cls.grade}年)</span>
                            </div>
                        `;
                        classListContainer.appendChild(classItem);
                    });
                    addDragListeners(); // ドラッグリスナーは授業項目追加後呼び出し
                } else {
                    // サーバー応答が'success'でない場合エラーメッセージ出力
                    console.error('授業データの読み込みに失敗しました:', data.message);
                    classListContainer.innerHTML = `<p class="message error">${data.message}</p>`;
                }
            })
            .catch(error => {
                // ネットワークエラー発生時処理
                console.error('授業データの取得中にネットワークエラーが発生しました:', error);
                classListContainer.innerHTML = '<p class="message error">授業データの読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
            });
    }

    // --- 3.2. ドラッグ開始時イベントリスナー追加 ---
    function addDragListeners() {
        const classItems = document.querySelectorAll('.class-item');
        classItems.forEach(item => {
            item.addEventListener('dragstart', function(e) {
                draggedClass = this; // ドラッグ中の要素を保存
                e.dataTransfer.effectAllowed = 'move'; // ドラッグ効果設定
                e.dataTransfer.setData('text/plain', this.dataset.id); // IDをデータで送信
                this.classList.add('dragging'); // ドラッグ中であることを視覚的に表示
            });
            item.addEventListener('dragend', function() {
                this.classList.remove('dragging'); // ドラッグ終了後クラス削除
            });
        });
    }

    // --- 3.3. ドロップゾーン (時間割セル) イベントリスナー追加 ---
    function addDropListeners() {
        const timeSlots = timetableTable.querySelectorAll('.time-slot');
        timeSlots.forEach(slot => {
            // ドラッグ要素がドロップゾーン上を通過する時
            slot.addEventListener('dragover', function(e) {
                e.preventDefault(); // デフォルト動作 (要素を開くなど) を防止
                e.dataTransfer.dropEffect = 'move'; // ドロップ効果設定
                this.classList.add('drag-over'); // 視覚的フィードバック
            });
            // ドラッグ要素がドロップゾーンを離れる時
            slot.addEventListener('dragleave', function() {
                this.classList.remove('drag-over'); // 視覚的フィードバック削除
            });
            // ドロップ時
            slot.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('drag-over');

                if (!draggedClass) return; // ドラッグ中の授業がなければ終了

                // ドラッグされた授業データ取得
                const classId = draggedClass.dataset.id;
                const className = draggedClass.dataset.name;
                const classCredit = draggedClass.dataset.credit;
                const classGrade = draggedClass.dataset.grade;
                const classCategory1 = draggedClass.dataset.category1;
                const classCategory2 = draggedClass.dataset.category2;
                
                // すでに授業があるセルか確認
                if (this.classList.contains('filled-primary')) {
                    alert('この時間枠にはすでに授業があります。');
                    return;
                }

                // セルに授業情報表示
                this.innerHTML = `
                    <span class="class-name-in-cell" data-class-id="${classId}">${className}</span>
                    <span class="class-credit-in-cell">${classCredit}単位</span>
                    <span class="category-display-in-cell">${classGrade}年 / ${classCategory1} / ${classCategory2}</span>
                    <button class="remove-button">&times;</button>
                `;
                this.classList.add('filled-primary'); // セルが埋まったことを表示
                // 削除ボタンにイベントリスナー追加
                this.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
            });
        });
    }

    addDropListeners(); // 初期ドロップリスナーを追加

    // --- 3.4. 時間割から授業削除 ---
    function removeClassFromTimetable(event) {
        const cell = event.target.closest('.time-slot'); // ボタンの最も近い'.time-slot'親要素を検索
        if (cell) {
            cell.innerHTML = ''; // セル内容を空にする
            cell.classList.remove('filled-primary'); // 'filled-primary'クラスを削除
        }
    }

    // --- 3.5. 時間割保存機能 ---
    function saveTimetable() {
        if (currentUserId === null) {
            alert('ログインしていません。ログイン後に時間割を保存できます。');
            window.location.href = 'login.php'; // ログインページへリダイレクト
            return;
        }

        const timetableData = []; // 保存する時間割データ配列
        // 埋められたすべての時間割セルを巡回
        timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
            const classId = cell.querySelector('.class-name-in-cell').dataset.classId;
            // index.phpでdata-day属性が英語の曜日に設定されていることを前提とします。
            const day = cell.dataset.day; 
            const period = cell.dataset.period;

            // データ構造に合わせてプッシュ
            timetableData.push({
                class_id: classId,
                day_of_week: day,
                period: period
            });
        });

        // save_timetable.phpへデータを送信
        fetch('save_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json' // JSON形式でデータ送信
            },
            body: JSON.stringify({
                user_id: currentUserId,
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
            } else {
                alert('時間割の保存に失敗しました: ' + data.message);
            }
        })
        .catch(error => {
            console.error('時間割の保存中にエラーが発生しました:', error);
            alert('時間割の保存中にエラーが発生しました。ネットワーク接続を確認してください。');
        });
    }

    // --- 3.6. 保存された時間割読み込み ---
    function loadTimetable() {
        if (currentUserId === null) {
            console.log("ユーザーがログインしていません。保存された時間割をロードしません。");
            return;
        }

        // get_timetable.phpからユーザー時間割データを取得
        fetch(`get_timetable.php?user_id=${currentUserId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // 既存の時間割を初期化
                    timetableTable.querySelectorAll('.time-slot.filled-primary').forEach(cell => {
                        cell.innerHTML = '';
                        cell.classList.remove('filled-primary');
                    });

                    // 読み込んだ時間割データに基づいてセルを埋める
                    data.timetable.forEach(entry => {
                        // セル選択時、data-day属性が英語の曜日に変更されていることを前提にセレクターもそれに合わせて修正
                        const cellSelector = `.time-slot[data-day="${entry.day_of_week}"][data-period="${entry.period}"]`;
                        const targetCell = timetableTable.querySelector(cellSelector);

                        if (targetCell) {
                            // get_timetable.phpから授業情報 (name, credit, grade, category1, category2) を一緒に返すと仮定します。
                            // もし返さない場合、この部分でエラーが発生する可能性があります。(DBから取得するように修正が必要)
                            const className = entry.class_name || '不明な授業'; // 授業名
                            const classCredit = entry.class_credit || '?'; // 単位
                            const classGrade = entry.grade || ''; // 学年
                            const classCategory1 = entry.category1 || ''; // カテゴリ1 (例: 専門/教養)
                            const classCategory2 = entry.category2 || ''; // カテゴリ2

                            targetCell.innerHTML = `
                                <span class="class-name-in-cell" data-class-id="${entry.class_id}">${className}</span>
                                <span class="class-credit-in-cell">${classCredit}単位</span>
                                <span class="category-display-in-cell">${classGrade}年 / ${classCategory1} / ${classCategory2}</span>
                                <button class="remove-button">&times;</button>
                            `;
                            targetCell.classList.add('filled-primary');
                            targetCell.querySelector('.remove-button').addEventListener('click', removeClassFromTimetable);
                        } else {
                            console.warn(`時間割セルが見つかりませんでした: Day ${entry.day_of_week}, Period ${entry.period}`);
                        }
                    });
                    console.log('時間割が正常にロードされました。');
                } else {
                    console.error('時間割のロードに失敗しました:', data.message);
                }
            })
            .catch(error => {
                console.error('時間割のロード中にエラーが発生しました:', error);
            });
    }

    // =========================================================
    // 4. イベントリスナー登録と初期実行
    // =========================================================

    // フィルターフォーム送信イベント
    if (classFilterForm) {
        classFilterForm.addEventListener('submit', function(event) {
            event.preventDefault(); // フォームのデフォルト送信を防止
            fetchAndDisplayClasses(); // フィルターされた授業リストを読み込み
        });
        // フィルター変更時に自動的に授業リストを更新したい場合は、以下のコメントを解除してください。
        // gradeSelect.addEventListener('change', fetchAndDisplayClasses);
        // termSelect.addEventListener('change', fetchAndDisplayClasses);
        // facultySelect.addEventListener('change', fetchAndDisplayClasses);
    }
    
    // ページロード時に授業リストを一度読み込みます。
    fetchAndDisplayClasses();

    // ドロップリスナーはページロード時に一度だけ追加すれば良いです。(セルは固定されているため)
    addDropListeners();

    // 時間割保存ボタンクリックイベント
    if (saveTimetableButton) {
        saveTimetableButton.addEventListener('click', saveTimetable);
    }

    // ログイン済みユーザーの場合、保存された時間割を自動的に読み込みます。
    if (currentUserId !== null) {
        loadTimetable();
    } else {
        // このメッセージがmain_script.js:278に該当するはずです。
        console.log("ユーザーがログインしていません。時間割の自動ロードは行われません。");
    }
}); // DOMContentLoaded 閉じ括弧