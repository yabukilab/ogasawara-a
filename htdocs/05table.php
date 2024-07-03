<!--05table.php-->
<?php
session_start();
require('db.php');
?>

<?php
$date=date('Y-m-d');
$today=date('n月j日');
$weekday=date('w');
$weekdays=['日','月','火','水','木','金','土'];
$weekday_japanese=$weekdays[$weekday];
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>予約完了ページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
            <h1>CIT Sports</h1>
            <h2>予約完了</h2>

            
                <?php
                echo "<div>{$today}（{$weekday_japanese}）</div>";
                ?>
            <?php
            $sportsList = ["卓球", "バスケ", "スカッシュ", "クライミング", "テニス", "野球", "サッカー"];
            $timeSlots = ["10:15-10:45", "10:45-11:15", "11:15-11:45", "11:45-12:15","12:45-13:15", "13:15-13:45", "13:45-14:15", "14:15-14:45","14:45-15:15", "15:15-15:45", "15:45-16:15", "16:15-16:45"];
            ?>

<div class="hyou">
    <table>
        <thead>
            <tr>
                <th>施設/時間　　　　　</th>
                <?php foreach ($timeSlots as $time) { ?>
                    <th><?php echo $time; ?></th>
                <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($sportsList as $sports) {
                echo "<tr>";
                echo "<th>{$sports}</th>";
                foreach ($timeSlots as $time) {
                    // クエリを準備
                    $sql = "SELECT * FROM reservations WHERE facility = :facility AND time = :time AND date = :date";
                    $sth = $db->prepare($sql);
                    // プレースホルダに変数をバインド
                    $sth->bindParam(':facility', $sports);
                    $sth->bindParam(':time', $time);
                    $sth->bindParam(':date', $date);
                    // クエリを実行
                    $sth->execute();
                    $result = $sth->fetchAll(PDO::FETCH_ASSOC);
                    // 結果を確認
                    if (count($result) > 0) {
                        echo "<td class='no'>×</td>";
                    } else {
                        echo "<td><form action='06reservation.php' method='POST'>
                                <input type='hidden' name='sports' value='" . htmlspecialchars($sports, ENT_QUOTES, 'UTF-8') . "'>
                                <input type='hidden' name='time' value='" . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . "'>
                                <input type='submit' style='color:red; font-size: 70px; background: none; border: none; cursor: pointer;' value='○'>
                              </form></td>";
                    }
                }
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</div>
            </div>
        </div>
    </body>
</html>