<?php
session_start();
require('db.php');

$date=date('Y-m-d');
$today=date('n月j日');
$weekday=date('w');
$weekdays=['日','月','火','水','木','金','土'];
$weekday_japanese=$weekdays[$weekday];

if(isset($_SESSION['student_number'])){
    $student_number=$_SESSION['student_number'];
}else{
    $student_number="";
}

// ログアウト処理
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}
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
            <div><img src="CIT_Sports.jpg" alt="test" width="80%" height="80%"></div>
            <div class="number2">学籍番号:<?php echo htmlspecialchars($student_number); ?>　</div>
            <h2>予約状況</h2>
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
                        <tr style="height: auto;">
                            <th>施設/時間　　　　　</th>
                            <?php foreach ($timeSlots as $time) { ?>
                                <th><?php echo $time; ?></th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($sportsList as $sports) {
                            echo "<tr style='height: auto;'>";
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
                                    echo "<td style='text-align: center;'><div style='color: blue; font-size: 50px; background: white; border: none; cursor: pointer;'>×</div></td>";
                                } else {
                                    echo "<td style='text-align: center;'><form action='06reservation.php' method='POST'>
                                            <input type='hidden' name='student_number' value='" . htmlspecialchars($student_number, ENT_QUOTES, 'UTF-8') . "'>
                                            <input type='hidden' name='sports' value='" . htmlspecialchars($sports, ENT_QUOTES, 'UTF-8') . "'>
                                            <input type='hidden' name='time' value='" . htmlspecialchars($time, ENT_QUOTES, 'UTF-8') . "'>
                                            <input type='submit' style='color:red; font-size: 50px; background: white; border: none; cursor: pointer; text-align: center;' value='○'>
                                        </form></td>";
                                }
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <!-- ログアウトボタン -->
            <form action="" method="post">
                <input type="submit" name="logout" value="ログアウト" class="button">
            </form>
        </div>
    </body>
</html>
