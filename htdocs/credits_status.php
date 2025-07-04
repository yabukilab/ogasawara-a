<?php
session_start(); // 세션 시작

// 進級判定などの処理の中に追加
$requiredCreditsByCategory = [
    "基幹" => 30,
    "専門" => 60,
    "一般" => 30
];

// カテゴリ別単位取得
$sql = "
    SELECT c.category1, SUM(c.credit) AS total
    FROM user_timetables ut
    JOIN class c ON ut.class_id = c.id
    WHERE ut.user_id = :user_id
    GROUP BY c.category1
";
$stmt = $db->prepare($sql);
$stmt->execute([':user_id' => $userId]);

// フェッチ形式：[カテゴリ名 => 合計単位]
$categoryCredits = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 不足単位計算
$missingByCategory = [];
foreach ($requiredCreditsByCategory as $category => $required) {
    $earned = isset($categoryCredits[$category]) ? $categoryCredits[$category] : 0;
    $missing = max(0, $required - $earned);
    $missingByCategory[$category] = [
        '取得済み' => $earned,
        '不足' => $missing
    ];
}

// 表示
echo "<h3>カテゴリ別 単位取得状況</h3>";
echo "<ul>";
foreach ($missingByCategory as $category => $data) {
    echo "<li>{$category}：{$data['取得済み']} / {$requiredCreditsByCategory[$category]} 単位";
    if ($data['不足'] > 0) {
        echo "（あと <strong style='color:red'>{$data['不足']}</strong> 単位必要）";
    } else {
        echo "（<span style='color:green'>要件達成</span>）";
    }
    echo "</li>";
}
echo "</ul>";
