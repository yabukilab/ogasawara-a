<!--06reservation.php-->

<?php
$date=date('Y-m-d');
$today=date('n月j日');
$weekday=date('w');
$weekdays=['日','月','火','水','木','金','土'];
$weekday_japanese=$weekdays[$weekday];
?>

<?php
if($_SERVER["REQUEST_METHOD"]=="POST"){
    $sports=$_POST['sports'];
    $time=$_POST['time'];
    $student_number=$_POST['student_number'];
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>予約確認ページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
            <h1>CIT Sports</h1>
            <div>学籍番号:<?php echo htmlspecialchars($student_number); ?></div>
            <h2>予約確認</h2>

            <?php
                echo "<div>{$today}（{$weekday_japanese}）</div>";
            ?>

            <form action="create.php" method="post">
                <div>以下の内容で予約しますか？</div>
                <div>
                <?php echo htmlspecialchars($sports); ?>
                <input type="hidden" name="sports" value="<?php echo htmlspecialchars($sports); ?>">
                </div>
                <div>
                <?php echo htmlspecialchars($time); ?>
                <input type="hidden" name="time" value="<?php echo htmlspecialchars($time); ?>">
                </div>
                <input type="hidden" name="student_number" value="<?php echo htmlspecialchars($student_number); ?>">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($date); ?>">
                <input type="button" value="前ページに戻る" onclick="history.back()" class="button">
                <br>
                <br>
                <input type="submit" value="予約する" class="button">
            </form>
        </div>
    </body>
</html>