<?php
// HTTP POST 요청을 통해 JSON 데이터를 받음
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$finalizedTimetable = [];
$totalCredits = 0;

if ($data) {
    if (isset($data['timetable'])) {
        $finalizedTimetable = $data['timetable'];
    }
    if (isset($data['totalCredits'])) {
        $totalCredits = $data['totalCredits'];
    }
} else {
    // POST 요청이 없거나 데이터가 없는 경우 (예: 직접 URL로 접근 시)
    // 세션 등을 사용하여 데이터를 유지할 수도 있지만, 여기서는 간단히 처리
    // 또는 에러 메시지를 표시
    // echo "授業データがありません。時間割登録ページからアクセスしてください。"; // 수업 데이터가 없습니다. 시간표 등록 페이지에서 접근해주세요.
    // exit;
    // 임시 데이터 (디버깅 또는 직접 접근 시)
    $finalizedTimetable = [
        // 예시 데이터 (실제 사용 시에는 POST 데이터로 채워짐)
        // ['id' => '1', 'name' => '日本語基礎', 'credit' => 2, 'term' => '1', 'day' => '月', 'period' => 1, 'linkedPeriod' => 2],
        // ['id' => '2', 'name' => '数学入門', 'credit' => 3, 'term' => '1', 'day' => '水', 'period' => 3, 'linkedPeriod' => 4],
    ];
    $totalCredits = 0; // 실제 데이터가 없으므로 0으로 초기화
}

// 학기 번호를 일본어 명칭으로 변환하는 함수 (index.php와 동일)
function getTermName($term_num) {
    switch ($term_num) {
        case 1: return '前期';
        case 2: return '後期';
        default: return '不明';
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>確定された時間割 (Finalized Timetable)</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { 
            background-color: #fff; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 800px; 
            margin: 30px auto; 
        }
        h1 { color: #333; text-align: center; margin-bottom: 25px; }
        h2 { color: #555; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #e9e9e9; }
        .total-credits { 
            font-size: 1.5em; 
            font-weight: bold; 
            color: #007bff; 
            text-align: right; 
            margin-top: 20px; 
            padding-top: 15px; 
            border-top: 2px solid #007bff; 
        }
        .no-classes { text-align: center; color: #888; font-style: italic; margin-top: 20px; }
        .back-button { 
            display: block; 
            width: fit-content; 
            margin: 30px auto 0; 
            padding: 10px 20px; 
            background-color: #6c757d; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            text-align: center; 
        }
        .back-button:hover { background-color: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h1>確定された時間割</h1>

        <h2>登録授業一覧</h2>
        <?php if (!empty($finalizedTimetable)): ?>
            <table>
                <thead>
                    <tr>
                        <th>曜日</th>
                        <th>時限</th>
                        <th>授業名</th>
                        <th>単位</th>
                        <th>学期</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finalizedTimetable as $class): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($class['day']); ?></td>
                            <td><?php echo htmlspecialchars($class['period'] . '限 - ' . $class['linkedPeriod'] . '限'); ?></td>
                            <td><?php echo htmlspecialchars($class['name']); ?></td>
                            <td><?php echo htmlspecialchars($class['credit']); ?></td>
                            <td><?php echo getTermName(htmlspecialchars($class['term'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-classes">登録された授業がありません。</p>
        <?php endif; ?>

        <div class="total-credits">
            合計単位数: <?php echo htmlspecialchars($totalCredits); ?>
        </div>

        <a href="index.php" class="back-button">時間割登録に戻る</a>
    </div>
</body>
</html>