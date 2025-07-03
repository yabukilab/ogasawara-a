<?php
session_start();
require_once 'db_config.php'; // DB 設定と h()、getTermName() 関数を含む

// ログイン確認
// student_number, user_id, department が全てセッションに存在するか確認
if (!isset($_SESSION['student_number']) || !isset($_SESSION['user_id']) || !isset($_SESSION['department'])) {
    header('Location: login.php'); // ログインしていない場合はログインページへリダイレクト
    exit;
}

$current_student_number = $_SESSION['student_number'];
$current_user_id = $_SESSION['user_id'];
$current_user_department = $_SESSION['department']; // セッションから学科情報を取得

$error = ''; // エラーメッセージ用変数

// 1. 卒業必要単位数の基準をロード
$graduationRequirements = [];
try {
    $stmt = $db->prepare("SELECT * FROM graduation_requirements WHERE department = :department");
    $stmt->execute([':department' => $current_user_department]);
    $graduationRequirements = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$graduationRequirements) {
<<<<<<< HEAD
        // 該当学科の卒業要件が存在しない場合には、デフォルト値を設定するか、エラー処理を行う.
        // 「プロジェクトマネジメント学科」に関するデータが存在しない可能性に備えて、デフォルト値を設定する.
        // ただし、上記のSQLを実行していれば、「プロジェクトマネジメント学科」に対してこの処理が実行されることはない.
=======
        // 해당 학과의 졸업 요건이 없는 경우 (또는 로드 실패 시)
        // 예를 들어 'プロジェクトマネジメント学科'에 대한 데이터가 'graduation_requirements' 테이블에
        // 없는 경우를 대비한 처리. 기본값을 설정하고 경고 메시지를 표시.
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
        $error = "Warning: この学科 ({$current_user_department}) の卒業要件が設定されていません。一般的な要件で表示します。";
        $graduationRequirements = [
            'total_required_credits' => 124, // 仮のデフォルト値 (総必要単位)
            'required_major_credits' => 88,  // 仮のデフォルト値 (専門科目必要単位)
            'required_liberal_arts_credits' => 36 // 仮のデフォルト値 (教養科目必要単位)
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to load graduation requirements: " . $e->getMessage()); // エラーログに記録
    $error = "卒業要件の読み込みに失敗しました。"; // ユーザーへの一般的なエラーメッセージ
    // DBエラー時もデフォルト値を設定し、処理を続行できるようにする
    $graduationRequirements = [
        'total_required_credits' => 124,
        'required_major_credits' => 88,
        'required_liberal_arts_credits' => 36
    ];
}

// 2. ユーザーが現在までに取得済みの全授業の単位と種類を計算
$totalAcquiredCredits = 0;
<<<<<<< HEAD
$acquiredMajorCredits = 0;     // 専門科目単位
$acquiredLiberalArtsCredits = 0; // 教養科目単位
// 必要に応じて、他の category1 用の変数も追加する.

$calculatedClassIds = []; // 同じ授業（class_id）が重複して単位が加算されないようにするための対策

try {
    // user_timetables テーブルと class テーブルを JOIN して、単位数と科目の種類（category1）を取得する.
    // 進級および卒業に必要な単位は、全体の取得単位数を基準とするため、学年（grade）の条件は含まない.
=======
$acquiredMajorCredits = 0;      // 専門科目 学点
$acquiredLiberalArtsCredits = 0; // 教養科目 学点

$calculatedClassIds = []; // 重複単位計算防止用 (class_id 基準)

try {
    // user_timetables と class テーブルを JOIN して単位と category1 (科目種類) を取得します。
    // 進級および卒業学点は、全体取得学点を基準にするため、学年(grade)条件は含めません。
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
    $stmt = $db->prepare("SELECT c.id as class_id, c.credit, c.category1
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.user_id = :user_id");
    $stmt->execute([':user_id' => $current_user_id]);
    $registeredClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registeredClasses as $class) {
        // 同じ class_id の授業が複数回時間割に登録されていても、単位は一度だけ加算
        if (!in_array($class['class_id'], $calculatedClassIds)) {
            $totalAcquiredCredits += (int)$class['credit'];
<<<<<<< HEAD
            $calculatedClassIds[] = $class['class_id']; // 重複を防ぐために追加.

            // category1 の値に応じて単位を分類.
=======
            $calculatedClassIds[] = $class['class_id']; // 重複防止のために追加

            // category1 の値に応じて単位を分類
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
            switch ($class['category1']) {
                case '専門科目':
                    $acquiredMajorCredits += (int)$class['credit'];
                    break;
                case '教養科目':
                    $acquiredLiberalArtsCredits += (int)$class['credit'];
                    break;
<<<<<<< HEAD
                // 他の category1（例：共通科目、自由科目など）があれば、ここに追加.
=======
                // 他の category1 値があればここに追加 (例: '共通科目', '自由科目' など)
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
            }
        }
    }
} catch (PDOException $e) {
    error_log("Failed to calculate acquired credits: " . $e->getMessage()); // エラーログに記録
    $error = "履修単位の計算に失敗しました。"; // ユーザーへの一般的なエラーメッセージ
}

<<<<<<< HEAD
// 3. 卒業までに必要な残りの単位数を計算.
=======
// 3. 卒業まで残りの単位数を計算 (既存ロジック)
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
$remainingTotalCredits = max(0, $graduationRequirements['total_required_credits'] - $totalAcquiredCredits);
$remainingMajorCredits = max(0, $graduationRequirements['required_major_credits'] - $acquiredMajorCredits);
$remainingLiberalArtsCredits = max(0, $graduationRequirements['required_liberal_arts_credits'] - $acquiredLiberalArtsCredits);

<<<<<<< HEAD
// 4. 進級条件の判定.
$promotionStatus = [];

// 1年次から2年次への進級条件：合計24単位取得.
=======
// 4. 進級条件チェック (追加されたロジック)
$promotionStatus = [];

// 1年生から2年生への進級条件: 総24単位
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
$promotionStatus['1_to_2'] = [
    'required_total' => 24,
    'current_total' => $totalAcquiredCredits,
    'eligible' => ($totalAcquiredCredits >= 24)
];

<<<<<<< HEAD
// 2年生から3年生への進級条件：合計64単位かつ専門科目44単位.
=======
// 2年生から3年生への進級条件: 総64単位 かつ 専門44単位
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
$promotionStatus['2_to_3'] = [
    'required_total' => 64,
    'required_major' => 44,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 64 && $acquiredMajorCredits >= 44)
];

<<<<<<< HEAD
// 3年生から4年生への進級条件：合計102単位かつ専門科目74単位.
=======
// 3年生から4年生への進級条件: 総102単位 かつ 専門74単位
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
$promotionStatus['3_to_4'] = [
    'required_total' => 102,
    'required_major' => 74,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 102 && $acquiredMajorCredits >= 74)
];

<<<<<<< HEAD
// これらの変数をHTMLに表示.
=======
// 現在選択されている学年 (URLパラメータから取得、デフォルトは2年生)
// これは「時間割を編集」や「確定済み時間割を見る」に戻るリンクで使用
$selectedGradeForLink = isset($_GET['grade_filter']) ? (int)$_GET['grade_filter'] : 2;

>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>単位取得状況 (Credit Acquisition Status)</title>
    <link rel="stylesheet" href="style.css"> <style>
        /* CSS スタイルは必要に応じて調整 */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f2f2f2;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }
        .user-info {
            text-align: right;
            margin-bottom: 10px;
            padding: 10px 20px;
            background-color: #e9ecef;
            border-bottom: 1px solid #dee2e6;
            font-size: 0.9em;
        }
        .user-info a {
            margin-left: 15px;
            color: #007bff;
            text-decoration: none;
        }
        .user-info a:hover {
            text-decoration: underline;
        }
        .credit-status-container {
            width: 100%;
            max-width: 700px; /* 少し広めに */
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 2em;
        }
        h2 {
            font-size: 1.6em;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #444;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
        }
        h3 {
            font-size: 1.4em;
            margin-top: 25px;
            margin-bottom: 10px;
            color: #555;
        }
        .credit-summary p, .promotion-summary p {
            font-size: 1.1em;
            margin-bottom: 10px;
            padding-left: 10px;
        }
        .credit-summary strong, .promotion-summary strong {
            color: #007bff;
        }
        .credit-details, .promotion-details {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .credit-details ul, .promotion-details ul {
            list-style: none;
            padding: 0;
        }
        .credit-details ul li, .promotion-details ul li {
            background: #f9f9f9;
<<<<<<< HEAD
            padding: 10px 15px;
            margin-bottom: 8px;
            border-left: 5px solid #28a745; /* 緑色の枠線 */
            border-radius: 5px;
=======
            padding: 15px 20px; /* パディングを増やす */
            margin-bottom: 10px; /* マージンを増やす */
            border-left: 5px solid #28a745; /* 基本の緑色テクスチャ */
            border-radius: 8px; /* 角を丸める */
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
            display: flex;
            flex-direction: column; /* 項目を縦に配置 */
            justify-content: space-between;
<<<<<<< HEAD
            align-items: center;
            flex-wrap: wrap; /* 内容が長くなる場合は改行する */
        }
        .promotion-details ul li {
            border-left: 5px solid #007bff; /* 青色の枠線で表示 */
        }
        .credit-details ul li.met-requirement, .promotion-details ul li.met-requirement {
            border-left-color: #28a745; /* 条件を満たしたら緑色で表示 */
        }
        .credit-details ul li.not-met-requirement, .promotion-details ul li.not-met-requirement {
            border-left-color: #dc3545; /* 条件を満たさない場合は赤色 */
=======
            align-items: flex-start; /* 左寄せ */
            transition: all 0.3s ease;
        }
        .promotion-details ul li {
            border-left: 5px solid #007bff; /* 進級ステータスは青色テクスチャ */
        }
        .credit-details ul li.met-requirement, .promotion-details ul li.met-requirement {
            border-left-color: #28a745; /* 条件を満足すれば緑色 */
        }
        .credit-details ul li.not-met-requirement, .promotion-details ul li.not-met-requirement {
            border-left-color: #dc3545; /* 条件を満足しないと赤色 */
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
        }
        .credit-details ul li span, .promotion-details ul li span {
            font-weight: bold;
            color: #333;
<<<<<<< HEAD
            flex-basis: 100%; /* タイトルは改行せずに1行で表示 */
            margin-bottom: 5px;
        }
        .credit-details ul li .remaining-credits, .promotion-details ul li .status-text {
            color: #dc3545; /* 残りの単位は赤色で表示 */
            font-size: 1.1em;
            margin-left: auto; /* 右揃えにする */
        }
        .promotion-details ul li .status-text.ok {
            color: #28a745; /* 「OK」は緑色で表示 */
=======
            margin-bottom: 8px; /* 下部マージン */
            font-size: 1.1em;
        }
        .credit-details ul li .remaining-credits, .promotion-details ul li .status-text {
            color: #dc3545; /* 残り単位は赤色 */
            font-size: 1.2em; /* フォントサイズを大きく */
            align-self: flex-end; /* 右下に配置 */
            margin-top: 10px; /* 上部マージン */
        }
        .promotion-details ul li .status-text.ok {
            color: #28a745; /* 'OK'は緑色 */
        }
        .status-text.ng { /* 'NG'や不足単位数表示 */
            color: #dc3545;
>>>>>>> 3c81779a5228a60594a6e72b00947930ec207567
        }
        .navigation-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .navigation-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            margin: 0 8px; /* ボタン間の間隔 */
            transition: background-color 0.3s ease;
        }
        .navigation-buttons a:hover {
            background-color: #0056b3;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 20px;
        }
        /* 各条件の詳細行のスタイル */
        .credit-details ul li div,
        .promotion-details ul li div {
            font-size: 0.95em;
            color: #666;
            margin-top: 5px;
            width: 100%; /* 전체 너비를 차지하도록 */
        }
    </style>
</head>
<body>
    <div class="user-info">
        ログイン中のユーザー: <?php echo htmlspecialchars($current_student_number); ?> (学科: <?php echo htmlspecialchars($current_user_department); ?>)
        <a href="logout.php">ログアウト</a>
    </div>

    <div class="credit-status-container">
        <h1>単位取得状況</h1>

        <?php if (!empty($error)): ?>
            <p class="message error"><?php echo h($error); ?></p>
        <?php else: ?>
            <div class="credit-summary">
                <h2>卒業要件</h2>
                <p>卒業必要総単位数: <strong><?php echo h($graduationRequirements['total_required_credits']); ?></strong> 単位</p>
                <p>現在取得済み総単位数: <strong><?php echo h($totalAcquiredCredits); ?></strong> 単位</p>
                <p>卒業まで残り: <strong style="color: #dc3545;"><?php echo h($remainingTotalCredits); ?></strong> 単位</p>
            </div>

            <div class="credit-details">
                <h3>種別ごとの単位状況 (卒業要件)</h3>
                <ul>
                    <li class="<?= ($remainingMajorCredits <= 0) ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>専門科目</span>
                        <div>取得済み: <strong><?php echo h($acquiredMajorCredits); ?></strong> 単位 / 必要: <?php echo h($graduationRequirements['required_major_credits']); ?> 単位</div>
                        <span class="remaining-credits">残り: <?php echo h($remainingMajorCredits); ?></span>
                    </li>
                    <li class="<?= ($remainingLiberalArtsCredits <= 0) ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>教養科目</span>
                        <div>取得済み: <strong><?php echo h($acquiredLiberalArtsCredits); ?></strong> 単位 / 必要: <?php echo h($graduationRequirements['required_liberal_arts_credits']); ?> 単位</div>
                        <span class="remaining-credits">残り: <?php echo h($remainingLiberalArtsCredits); ?></span>
                    </li>
                    </ul>
            </div>

            <div class="promotion-details">
                <h2>進級条件</h2>
                <ul>
                    <li class="<?= $promotionStatus['1_to_2']['eligible'] ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>1年生から2年生への進級</span>
                        <div>
                            必要総単位: <?= h($promotionStatus['1_to_2']['required_total']) ?> 単位 (取得済み: <?= h($promotionStatus['1_to_2']['current_total']) ?> 単位)
                        </div>
                        <span class="status-text <?= $promotionStatus['1_to_2']['eligible'] ? 'ok' : 'ng' ?>">
                            <?= $promotionStatus['1_to_2']['eligible'] ? 'OK' : '残り ' . max(0, $promotionStatus['1_to_2']['required_total'] - $promotionStatus['1_to_2']['current_total']) . '単位' ?>
                        </span>
                    </li>
                    <li class="<?= $promotionStatus['2_to_3']['eligible'] ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>2年生から3年生への進級</span>
                        <div>
                            必要総単位: <?= h($promotionStatus['2_to_3']['required_total']) ?> 単位 (取得済み: <?= h($promotionStatus['2_to_3']['current_total']) ?> 単位)<br>
                            必要専門単位: <?= h($promotionStatus['2_to_3']['required_major']) ?> 単位 (取得済み: <?= h($promotionStatus['2_to_3']['current_major']) ?> 単位)
                        </div>
                        <span class="status-text <?= $promotionStatus['2_to_3']['eligible'] ? 'ok' : 'ng' ?>">
                            <?php
                            $msg = [];
                            if ($totalAcquiredCredits < $promotionStatus['2_to_3']['required_total']) {
                                $msg[] = '総' . max(0, $promotionStatus['2_to_3']['required_total'] - $promotionStatus['2_to_3']['current_total']) . '単位不足';
                            }
                            if ($acquiredMajorCredits < $promotionStatus['2_to_3']['required_major']) {
                                $msg[] = '専門' . max(0, $promotionStatus['2_to_3']['required_major'] - $promotionStatus['2_to_3']['current_major']) . '単位不足';
                            }
                            echo $promotionStatus['2_to_3']['eligible'] ? 'OK' : implode(', ', $msg);
                            ?>
                        </span>
                    </li>
                    <li class="<?= $promotionStatus['3_to_4']['eligible'] ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>3年生から4年生への進級</span>
                        <div>
                            必要総単位: <?= h($promotionStatus['3_to_4']['required_total']) ?> 単位 (取得済み: <?= h($promotionStatus['3_to_4']['current_total']) ?> 単位)<br>
                            必要専門単位: <?= h($promotionStatus['3_to_4']['required_major']) ?> 単位 (取得済み: <?= h($promotionStatus['3_to_4']['current_major']) ?> 単位)
                        </div>
                        <span class="status-text <?= $promotionStatus['3_to_4']['eligible'] ? 'ok' : 'ng' ?>">
                            <?php
                            $msg = [];
                            if ($totalAcquiredCredits < $promotionStatus['3_to_4']['required_total']) {
                                $msg[] = '総' . max(0, $promotionStatus['3_to_4']['required_total'] - $promotionStatus['3_to_4']['current_total']) . '単位不足';
                            }
                            if ($acquiredMajorCredits < $promotionStatus['3_to_4']['required_major']) {
                                $msg[] = '専門' . max(0, $promotionStatus['3_to_4']['required_major'] - $promotionStatus['3_to_4']['current_major']) . '単位不足';
                            }
                            echo $promotionStatus['3_to_4']['eligible'] ? 'OK' : implode(', ', $msg);
                            ?>
                        </span>
                    </li>
                </ul>
            </div>
        <?php endif; ?>

        <div class="navigation-buttons">
            <a href="index.php">時間割編集に戻る</a>
            <a href="confirmed_timetable.php?grade_filter=<?= htmlspecialchars($selectedGradeForLink) ?>">確定済み時間割を見る</a>
        </div>
    </div>
</body>
</html>