<?php
require('db.php');

// データを取得して表示する関数
function displayTable($db, $tableName) {
    try {
        $stmt = $db->query("SELECT * FROM $tableName");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            echo "<h2>Table: " . h($tableName) . "</h2>";
            echo "<table border='1'>";
            echo "<tr>";
            foreach (array_keys($results[0]) as $column) {
                echo "<th>" . h($column) . "</th>";
            }
            echo "</tr>";

            foreach ($results as $row) {
                echo "<tr>";
                foreach ($row as $value) {
                    echo "<td>" . h($value) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No data found in table " . h($tableName) . ".</p>";
        }
    } catch (PDOException $e) {
        echo "Error: " . h($e->getMessage());
    }
}

// ページのヘッダー
echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Database Tables</title>";
echo "</head>";
echo "<body>";
echo "<h1>Database Tables</h1>";

// テーブルを表示
displayTable($db, 'reservations');
displayTable($db, 'users');

// ページのフッター
echo "</body>";
echo "</html>";
?>
