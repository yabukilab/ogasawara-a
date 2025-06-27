<?php
session_start();
require_once 'db_config.php'; // DB 設定と h()、getTermName() 関数を含む

// ログイン確認
if (!isset($_SESSION['student_number']) || !isset($_SESSION['user_id']) || !isset($_SESSION['department'])) {
    header('Location: login.php');
    exit;
}

$current_student_number = $_SESSION['student_number'];
$current_user_id = $_SESSION['user_id'];
$current_user_department = $_SESSION['department'];

$error = ''; // エラーメッセージ用変数

// 1. 卒業必要単位数の基準をロード
$graduationRequirements = [];
try {
    $stmt = $db->prepare("SELECT * FROM graduation_requirements WHERE department = :department");
    $stmt->execute([':department' => $current_user_department]);
    $graduationRequirements = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$graduationRequirements) {
        // 該当学科の卒業要件が存在しない場合には、デフォルト値を設定するか、エラー処理を行う.
        // 「プロジェクトマネジメント学科」に関するデータが存在しない可能性に備えて、デフォルト値を設定する.
        // ただし、上記のSQLを実行していれば、「プロジェクトマネジメント学科」に対してこの処理が実行されることはない.
        $error = "Warning: この学科 ({$current_user_department}) の卒業要件が設定されていません。一般的な要件で表示します。";
        $graduationRequirements = [
            'total_required_credits' => 124, // 仮のデフォルト値
            'required_major_credits' => 88,  // 仮のデフォルト値
            'required_liberal_arts_credits' => 36 // 仮のデフォルト値
        ];
    }
} catch (PDOException $e) {
    error_log("Failed to load graduation requirements: " . $e->getMessage());
    $error = "卒業要件の読み込みに失敗しました。";
}

// 2. ユーザーが現在までに取得済みの全授業の単位と種類を計算
$totalAcquiredCredits = 0;
$acquiredMajorCredits = 0;     // 専門科目単位
$acquiredLiberalArtsCredits = 0; // 教養科目単位
// 必要に応じて、他の category1 用の変数も追加する.

$calculatedClassIds = []; // 同じ授業（class_id）が重複して単位が加算されないようにするための対策

try {
    // user_timetables テーブルと class テーブルを JOIN して、単位数と科目の種類（category1）を取得する.
    // 進級および卒業に必要な単位は、全体の取得単位数を基準とするため、学年（grade）の条件は含まない.
    $stmt = $db->prepare("SELECT c.id as class_id, c.credit, c.category1
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.user_id = :user_id");
    $stmt->execute([':user_id' => $current_user_id]);
    $registeredClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registeredClasses as $class) {
        if (!in_array($class['class_id'], $calculatedClassIds)) {
            $totalAcquiredCredits += (int)$class['credit'];
            $calculatedClassIds[] = $class['class_id']; // 重複を防ぐために追加.

            // category1 の値に応じて単位を分類.
            switch ($class['category1']) {
                case '専門科目':
                    $acquiredMajorCredits += (int)$class['credit'];
                    break;
                case '教養科目':
                    $acquiredLiberalArtsCredits += (int)$class['credit'];
                    break;
                // 他の category1（例：共通科目、自由科目など）があれば、ここに追加.
            }
        }
    }
} catch (PDOException $e) {
    error_log("Failed to calculate acquired credits: " . $e->getMessage());
    $error = "履修単位の計算に失敗しました。";
}

// 3. 卒業までに必要な残りの単位数を計算.
$remainingTotalCredits = max(0, $graduationRequirements['total_required_credits'] - $totalAcquiredCredits);
$remainingMajorCredits = max(0, $graduationRequirements['required_major_credits'] - $acquiredMajorCredits);
$remainingLiberalArtsCredits = max(0, $graduationRequirements['required_liberal_arts_credits'] - $acquiredLiberalArtsCredits);

// 4. 進級条件の判定.
$promotionStatus = [];

// 1年次から2年次への進級条件：合計24単位取得.
$promotionStatus['1_to_2'] = [
    'required_total' => 24,
    'current_total' => $totalAcquiredCredits,
    'eligible' => ($totalAcquiredCredits >= 24)
];

// 2年生から3年生への進級条件：合計64単位かつ専門科目44単位.
$promotionStatus['2_to_3'] = [
    'required_total' => 64,
    'required_major' => 44,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 64 && $acquiredMajorCredits >= 44)
];

// 3年生から4年生への進級条件：合計102単位かつ専門科目74単位.
$promotionStatus['3_to_4'] = [
    'required_total' => 102,
    'required_major' => 74,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 102 && $acquiredMajorCredits >= 74)
];

// これらの変数をHTMLに表示.
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>単位取得状況 (Credit Acquisition Status)</title>
    <link rel="stylesheet" href="style.css"> <style>
        .credit-status-container {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
        .credit-summary p, .promotion-summary p {
            font-size: 1.1em;
            margin-bottom: 10px;
        }
        .credit-summary strong, .promotion-summary strong {
            color: #007bff;
        }
        .credit-details, .promotion-details {
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .credit-details h2, .promotion-details h2 {
            font-size: 1.3em;
            margin-bottom: 15px;
            color: #333;
        }
        .credit-details ul, .promotion-details ul {
            list-style: none;
            padding: 0;
        }
        .credit-details ul li, .promotion-details ul li {
            background: #f9f9f9;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-left: 5px solid #28a745; /* 緑色の枠線 */
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
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
        }
        .credit-details ul li span, .promotion-details ul li span {
            font-weight: bold;
            color: #333;
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
        }
        .navigation-buttons {
            text-align: center;
            margin-top: 30px;
        }
        .navigation-buttons a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            margin: 0 5px;
        }
        .navigation-buttons a:hover {
            background-color: #0056b3;
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
                        取得済み: <strong><?php echo h($acquiredMajorCredits); ?></strong> 単位 / 必要: <?php echo h($graduationRequirements['required_major_credits']); ?> 単位
                        <span class="remaining-credits">残り: <?php echo h($remainingMajorCredits); ?></span>
                    </li>
                    <li class="<?= ($remainingLiberalArtsCredits <= 0) ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>教養科目</span>
                        取得済み: <strong><?php echo h($acquiredLiberalArtsCredits); ?></strong> 単位 / 必要: <?php echo h($graduationRequirements['required_liberal_arts_credits']); ?> 単位
                        <span class="remaining-credits">残り: <?php echo h($remainingLiberalArtsCredits); ?></span>
                    </li>
                    </ul>
            </div>

            <div class="promotion-details">
                <h2>進級条件</h2>
                <ul>
                    <li class="<?= $promotionStatus['1_to_2']['eligible'] ? 'met-requirement' : 'not-met-requirement' ?>">
                        <span>1年生から2年生への進級</span>
                        必要総単位: <?= h($promotionStatus['1_to_2']['required_total']) ?> 単位 (取得済み: <?= h($promotionStatus['1_to_2']['current_total']) ?> 単位)
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
            <a href="confirmed_timetable.php">確定済み時間割を見る</a>
        </div>
    </div>
</body>
</html>