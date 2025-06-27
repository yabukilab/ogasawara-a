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
        // 해당 학과의 졸업 요건이 없는 경우 기본값 설정 또는 에러 처리
        // 'プロジェクトマネジメント学科'에 대한 데이터가 없을 경우를 대비하여 기본값을 설정합니다.
        // 하지만 위 SQL을 실행했다면 이 블록은 'プロジェクトマネジメント学科'에 대해서는 실행되지 않습니다.
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
$acquiredMajorCredits = 0;     // 専門科目 학점
$acquiredLiberalArtsCredits = 0; // 教養科目 학점
// 필요한 경우 다른 category1에 대한 변수도 추가

$calculatedClassIds = []; // 중복 단위 계산 방지용 (class_id 기준)

try {
    // user_timetables와 class 테이블을 JOIN하여 단위와 category1 (과목 종류)를 가져옵니다.
    // 진급 및 졸업 학점은 전체 취득 학점을 기준으로 하므로, 학년(grade) 조건은 넣지 않습니다.
    $stmt = $db->prepare("SELECT c.id as class_id, c.credit, c.category1
                           FROM user_timetables ut
                           JOIN class c ON ut.class_id = c.id
                           WHERE ut.user_id = :user_id");
    $stmt->execute([':user_id' => $current_user_id]);
    $registeredClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($registeredClasses as $class) {
        if (!in_array($class['class_id'], $calculatedClassIds)) {
            $totalAcquiredCredits += (int)$class['credit'];
            $calculatedClassIds[] = $class['class_id']; // 중복 방지를 위해 추가

            // category1 값에 따라 학점을 분류
            switch ($class['category1']) {
                case '専門科目':
                    $acquiredMajorCredits += (int)$class['credit'];
                    break;
                case '教養科目':
                    $acquiredLiberalArtsCredits += (int)$class['credit'];
                    break;
                // 다른 category1 값이 있다면 여기에 추가 (예: '共通科目', '自由科目' 등)
            }
        }
    }
} catch (PDOException $e) {
    error_log("Failed to calculate acquired credits: " . $e->getMessage());
    $error = "履修単位の計算に失敗しました。";
}

// 3. 졸업까지 남은 단위 수 계산 (기존 로직)
$remainingTotalCredits = max(0, $graduationRequirements['total_required_credits'] - $totalAcquiredCredits);
$remainingMajorCredits = max(0, $graduationRequirements['required_major_credits'] - $acquiredMajorCredits);
$remainingLiberalArtsCredits = max(0, $graduationRequirements['required_liberal_arts_credits'] - $acquiredLiberalArtsCredits);

// 4. 진급 조건 체크 (추가된 로직)
$promotionStatus = [];

// 1학년에서 2학년으로 진급 조건: 총 24단위
$promotionStatus['1_to_2'] = [
    'required_total' => 24,
    'current_total' => $totalAcquiredCredits,
    'eligible' => ($totalAcquiredCredits >= 24)
];

// 2학년에서 3학년으로 진급 조건: 총 64단위 AND 전문 44단위
$promotionStatus['2_to_3'] = [
    'required_total' => 64,
    'required_major' => 44,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 64 && $acquiredMajorCredits >= 44)
];

// 3학년에서 4학년으로 진급 조건: 총 102단위 AND 전문 74단위
$promotionStatus['3_to_4'] = [
    'required_total' => 102,
    'required_major' => 74,
    'current_total' => $totalAcquiredCredits,
    'current_major' => $acquiredMajorCredits,
    'eligible' => ($totalAcquiredCredits >= 102 && $acquiredMajorCredits >= 74)
];

// 이제 이 변수들을 HTML에 표시
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
            border-left: 5px solid #28a745; /* 기본 초록색 테두리 */
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap; /* 내용이 길어질 경우 줄바꿈 */
        }
        .promotion-details ul li {
            border-left: 5px solid #007bff; /* 진급 상태는 파란색 테두리 */
        }
        .credit-details ul li.met-requirement, .promotion-details ul li.met-requirement {
            border-left-color: #28a745; /* 조건을 만족하면 초록색 */
        }
        .credit-details ul li.not-met-requirement, .promotion-details ul li.not-met-requirement {
            border-left-color: #dc3545; /* 조건을 만족하지 못하면 빨간색 */
        }
        .credit-details ul li span, .promotion-details ul li span {
            font-weight: bold;
            color: #333;
            flex-basis: 100%; /* 제목은 항상 한 줄에 */
            margin-bottom: 5px;
        }
        .credit-details ul li .remaining-credits, .promotion-details ul li .status-text {
            color: #dc3545; /* 남은 단위는 빨간색 */
            font-size: 1.1em;
            margin-left: auto; /* 오른쪽에 붙도록 */
        }
        .promotion-details ul li .status-text.ok {
            color: #28a745; /* 'OK'는 초록색 */
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