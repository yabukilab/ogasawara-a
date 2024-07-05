<?php
require('db.php');

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
                echo "<td><a href='delete.php?table=$tableName&id=" . h($row['ID']) . "'>Delete</a></td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "<a href='add.php?table=$tableName'>Add New Entry</a>";
        } else {
            echo "<p>No data found in table " . h($tableName) . ".</p>";
        }
    } catch (PDOException $e) {
        echo "Error: " . h($e->getMessage());
    }
}

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>Database Tables</title>";
echo "</head>";
echo "<body>";
echo "<h1>Database Tables</h1>";

displayTable($db, 'reservations');
displayTable($db, 'users');

echo "</body>";
echo "</html>";
?>
