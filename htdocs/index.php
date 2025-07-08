<?php
session_start(); // セッションを開始

// データベース接続ファイルを含める
require_once 'db.php';

// ログインしていない場合はログインページにリダイレクト
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// ログインユーザーの情報を取得 (必要に応じて利用)
$loggedInStudentNumber = h($_SESSION['student_number'] ?? '');
$loggedInDepartment = h($_SESSION['department'] ?? '');
$loggedInUserId = h($_SESSION['user_id'] ?? '');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業登録と時間割</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: "Inter", sans-serif;
            background-color: #f0f2f5; /* Light gray background */
        }
        /* Custom scrollbar for course list */
        .overflow-y-auto::-webkit-scrollbar {
            width: 8px;
        }
        .overflow-y-auto::-webkit-scrollbar-track {
            background: #e2e8f0; /* Light gray track */
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb {
            background: #94a3b8; /* Gray thumb */
            border-radius: 10px;
        }
        .overflow-y-auto::-webkit-scrollbar-thumb:hover {
            background: #64748b; /* Darker gray on hover */
        }
    </style>
</head>
<body class="p-4 md:p-8 min-h-screen flex flex-col items-center">
    <div class="container mx-auto bg-white rounded-xl shadow-lg p-6 md:p-10 w-full max-w-6xl">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800">授業登録システム</h1>
            <div class="text-right">
                <p class="text-gray-700">ようこそ、<span class="font-semibold text-indigo-600"><?php echo $loggedInStudentNumber; ?></span>さん</p>
                <p class="text-sm text-gray-500">(<?php echo $loggedInDepartment; ?>)</p>
                <a href="logout.php" class="text-red-500 hover:text-red-700 text-sm mt-1 inline-block">ログアウト</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 授業選択セクション -->
            <div class="lg:col-span-1 bg-gray-50 p-6 rounded-lg shadow-md">
                <h2 class="text-2xl font-semibold text-gray-700 mb-5">利用可能な授業</h2>
                <div id="course-list" class="space-y-4 max-h-96 overflow-y-auto pr-2">
                    <!-- 授業カードがここに動的に追加されます -->
                    <p id="loading-courses" class="text-gray-500 text-center">授業データを読み込み中...</p>
                </div>
            </div>

            <!-- 時間割と登録済み授業セクション -->
            <div class="lg:col-span-2">
                <div class="bg-gray-50 p-6 rounded-lg shadow-md mb-8">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-5">登録済み授業</h2>
                    <div id="registered-courses" class="space-y-3 max-h-64 overflow-y-auto pr-2">
                        <!-- 登録済み授業カードがここに動的に追加されます -->
                        <p id="no-registered-courses" class="text-gray-500 text-center">まだ授業が登録されていません。</p>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-200 text-right">
                        <p class="text-xl font-bold text-gray-800">合計取得単位: <span id="total-credits" class="text-indigo-600">0</span></p>
                    </div>
                </div>

                <!-- 時間割セクション -->
                <div class="bg-gray-50 p-6 rounded-lg shadow-md">
                    <h2 class="text-2xl font-semibold text-gray-700 mb-5">時間割</h2>
                    <div id="timetable-message" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                        <strong class="font-bold">時間割の重複!</strong>
                        <span class="block sm:inline" id="timetable-conflict-message"></span>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 bg-white">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">時限</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">月</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">火</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">水</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">木</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">金</th>
                                </tr>
                            </thead>
                            <tbody id="timetable-body" class="divide-y divide-gray-200">
                                <!-- 時間割の行がJavaScriptで生成されます -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ページロード時に実行される関数
        window.onload = function() {
            // 利用可能な授業のデータ (外部から取得されるためletで宣言)
            let courses = [];

            // DOM要素の取得
            const courseListDiv = document.getElementById('course-list');
            const registeredCoursesDiv = document.getElementById('registered-courses');
            const noRegisteredCoursesMessage = document.getElementById('no-registered-courses');
            const totalCreditsSpan = document.getElementById('total-credits');
            const timetableBody = document.getElementById('timetable-body');
            const timetableMessage = document.getElementById('timetable-message');
            const timetableConflictMessage = document.getElementById('timetable-conflict-message');
            const loadingCoursesMessage = document.getElementById('loading-courses');

            // 登録済み授業を追跡する配列と時間割のマップ
            let registeredCourseIds = new Set(); // 登録済みの授業IDを保持
            let timetable = {}; // 例: { '月-1': 'プログラミング基礎', '火-2': 'データ構造とアルゴリズム' }

            // 時間割の初期化
            const days = ['月', '火', '水', '木', '金'];
            const periods = ['1', '2', '3', '4', '5']; // 時限の数

            // 時間割テーブルの行を生成
            function initializeTimetable() {
                timetableBody.innerHTML = ''; // 既存の行をクリア
                periods.forEach(period => {
                    const row = document.createElement('tr');
                    row.classList.add('hover:bg-gray-50');
                    // 時限セル
                    const periodCell = document.createElement('td');
                    periodCell.classList.add('px-4', 'py-3', 'whitespace-nowrap', 'text-sm', 'font-medium', 'text-gray-900', 'bg-gray-50');
                    periodCell.textContent = period;
                    row.appendChild(periodCell);

                    days.forEach(day => {
                        const cell = document.createElement('td');
                        cell.id = `cell-${day}-${period}`; // セルにIDを設定
                        cell.classList.add('px-4', 'py-3', 'whitespace-nowrap', 'text-sm', 'text-gray-700', 'border-l', 'border-gray-100');
                        row.appendChild(cell);
                        // 時間割マップを初期化
                        timetable[`${day}-${period}`] = null; // 各セルをnullで初期化
                    });
                    timetableBody.appendChild(row);
                });
            }

            // 利用可能な授業カードをレンダリングする関数
            function renderAvailableCourses() {
                courseListDiv.innerHTML = ''; // リストをクリア
                if (courses.length === 0) {
                    courseListDiv.innerHTML = '<p class="text-gray-500 text-center">利用可能な授業がありません。</p>';
                    return;
                }

                courses.forEach(course => {
                    // 授業が既に登録されている場合はスキップ
                    if (registeredCourseIds.has(course.id)) {
                        return;
                    }

                    const courseCard = document.createElement('div');
                    courseCard.id = `available-${course.id}`;
                    courseCard.classList.add(
                        'bg-white', 'rounded-lg', 'shadow-sm', 'p-4', 'flex', 'flex-col', 'md:flex-row', 'items-center', 'justify-between',
                        'border', 'border-gray-200', 'hover:border-indigo-400', 'transition-all', 'duration-200'
                    );

                    const courseInfo = document.createElement('div');
                    courseInfo.classList.add('flex-grow', 'mb-2', 'md:mb-0');
                    courseInfo.innerHTML = `
                        <h3 class="text-lg font-semibold text-gray-800">${course.name}</h3>
                        <p class="text-sm text-gray-600">単位: ${course.credits} | ${course.day}曜 ${course.time}時限</p>
                    `;
                    courseCard.appendChild(courseInfo);

                    const addButton = document.createElement('button');
                    addButton.textContent = '登録';
                    addButton.classList.add(
                        'bg-indigo-500', 'hover:bg-indigo-600', 'text-white', 'font-bold', 'py-2', 'px-4',
                        'rounded-lg', 'transition-colors', 'duration-200', 'shadow-md', 'hover:shadow-lg',
                        'focus:outline-none', 'focus:ring-2', 'focus:ring-indigo-500', 'focus:ring-opacity-75'
                    );
                    addButton.onclick = () => addCourse(course);
                    courseCard.appendChild(addButton);

                    courseListDiv.appendChild(courseCard);
                });
            }

            // 登録済み授業をレンダリングする関数
            function renderRegisteredCourses() {
                registeredCoursesDiv.innerHTML = ''; // リストをクリア
                let totalCredits = 0;
                let hasRegisteredCourses = false;

                // courses配列が空の場合に備える
                if (courses.length === 0) {
                    noRegisteredCoursesMessage.classList.remove('hidden');
                    totalCreditsSpan.textContent = 0;
                    return;
                }

                courses.forEach(course => {
                    if (registeredCourseIds.has(course.id)) {
                        hasRegisteredCourses = true;
                        totalCredits += course.credits;

                        const registeredCard = document.createElement('div');
                        registeredCard.id = `registered-${course.id}`;
                        registeredCard.classList.add(
                            'bg-white', 'rounded-lg', 'shadow-sm', 'p-4', 'flex', 'flex-col', 'md:flex-row', 'items-center', 'justify-between',
                            'border', 'border-green-300', 'transition-all', 'duration-200'
                        );

                        const courseInfo = document.createElement('div');
                        courseInfo.classList.add('flex-grow', 'mb-2', 'md:mb-0');
                        courseInfo.innerHTML = `
                            <h3 class="text-lg font-semibold text-gray-800">${course.name}</h3>
                            <p class="text-sm text-gray-600">単位: ${course.credits} | ${course.day}曜 ${course.time}時限</p>
                        `;
                        registeredCard.appendChild(courseInfo);

                        const removeButton = document.createElement('button');
                        removeButton.textContent = '削除';
                        removeButton.classList.add(
                            'bg-red-500', 'hover:bg-red-600', 'text-white', 'font-bold', 'py-2', 'px-4',
                            'rounded-lg', 'transition-colors', 'duration-200', 'shadow-md', 'hover:shadow-lg',
                            'focus:outline-none', 'focus:ring-2', 'focus:ring-red-500', 'focus:ring-opacity-75'
                        );
                        removeButton.onclick = () => removeCourse(course);
                        registeredCard.appendChild(removeButton);

                        registeredCoursesDiv.appendChild(registeredCard);
                    }
                });

                // 「まだ授業が登録されていません」メッセージの表示/非表示
                if (!hasRegisteredCourses) {
                    noRegisteredCoursesMessage.classList.remove('hidden');
                } else {
                    noRegisteredCoursesMessage.classList.add('hidden');
                }

                totalCreditsSpan.textContent = totalCredits;
            }

            // 時間割を更新する関数
            function updateTimetableDisplay() {
                // すべての時間割セルをクリア
                days.forEach(day => {
                    periods.forEach(period => {
                        const cell = document.getElementById(`cell-${day}-${period}`);
                        if (cell) {
                            cell.textContent = '';
                            cell.classList.remove('bg-indigo-100', 'font-semibold', 'text-indigo-800'); // スタイルをリセット
                        }
                    });
                });

                // 登録された授業を時間割に配置
                registeredCourseIds.forEach(courseId => {
                    const course = courses.find(c => c.id === courseId);
                    if (course) {
                        const cellId = `cell-${course.day}-${course.time}`;
                        const cell = document.getElementById(cellId);
                        if (cell) {
                            cell.textContent = course.name;
                            cell.classList.add('bg-indigo-100', 'font-semibold', 'text-indigo-800'); // スタイルを追加
                        }
                    }
                });
            }

            // 授業を追加する関数
            function addCourse(course) {
                const timeSlotKey = `${course.day}-${course.time}`;

                // 時間割の重複チェック
                // timetable[timeSlotKey] が存在し、かつそれが現在の授業IDと異なる場合
                if (timetable[timeSlotKey] && timetable[timeSlotKey] !== course.name) { // 比較をIDから名前に変更
                    timetableConflictMessage.textContent = `${course.day}曜 ${course.time}時限は既に「${timetable[timeSlotKey]}」が登録されています。`;
                    timetableMessage.classList.remove('hidden');
                    // メッセージを3秒後に非表示にする
                    setTimeout(() => {
                        timetableMessage.classList.add('hidden');
                    }, 3000);
                    return; // 登録を中止
                }

                // 既に登録されている場合は何もしない
                if (registeredCourseIds.has(course.id)) {
                    return;
                }

                registeredCourseIds.add(course.id);
                timetable[timeSlotKey] = course.name; // 時間割に授業名を登録

                // UIを更新
                renderAvailableCourses();
                renderRegisteredCourses();
                updateTimetableDisplay();
                timetableMessage.classList.add('hidden'); // 競合メッセージを非表示にする
            }

            // 授業を削除する関数
            function removeCourse(course) {
                if (!registeredCourseIds.has(course.id)) {
                    return;
                }

                registeredCourseIds.delete(course.id);
                const timeSlotKey = `${course.day}-${course.time}`;
                if (timetable[timeSlotKey] === course.name) { // 比較をIDから名前に変更
                    timetable[timeSlotKey] = null; // 時間割から授業を削除
                }

                // UIを更新
                renderAvailableCourses();
                renderRegisteredCourses();
                updateTimetableDisplay();
            }

            // 授業データをロードする関数
            async function loadCourses() {
                loadingCoursesMessage.classList.remove('hidden'); // ローディングメッセージを表示
                try {
                    // PHPスクリプトのパスを適切に設定してください (例: 'get_courses.php')
                    const response = await fetch('get_courses.php');
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    courses = await response.json();
                    loadingCoursesMessage.classList.add('hidden'); // ローディングメッセージを非表示
                    // データロード後に初期レンダリングを実行
                    initializeTimetable();
                    renderAvailableCourses();
                    renderRegisteredCourses();
                    updateTimetableDisplay();
                } catch (error) {
                    console.error('授業データの取得に失敗しました:', error);
                    loadingCoursesMessage.textContent = '授業データの読み込みに失敗しました。';
                    loadingCoursesMessage.classList.remove('hidden');
                    // エラー時も初期レンダリングを試みる（空のリストで表示される）
                    initializeTimetable();
                    renderAvailableCourses();
                    renderRegisteredCourses();
                    updateTimetableDisplay();
                }
            }

            // ページロード時に授業データをロード
            loadCourses();
        };
    </script>
</body>
</html>