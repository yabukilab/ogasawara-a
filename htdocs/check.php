<?php
// db.php で定義されたデータベース接続コードを読み込む
require('db.php');

try {
    // データベースからデータを取得するクエリを作成
    $stmt = $db->query('SELECT * FROM reservations');

    // データの取得
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // テーブルの表示
    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Facility</th><th>Time</th><th>Reserver</th><th>Date</th></tr>';
    foreach ($reservations as $reservation) {
        echo '<tr>';
        echo '<td>' . h($reservation['ID']) . '</td>';
        echo '<td>' . h($reservation['facility']) . '</td>';
        echo '<td>' . h($reservation['time']) . '</td>';
        echo '<td>' . h($reservation['reserver']) . '</td>';
        echo '<td>' . h($reservation['date']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
?>
