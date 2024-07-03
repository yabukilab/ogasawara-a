<!--07reserved.php-->

<?php
session_start();
$date=date('Y-m-d');
$today=date('n月j日');
$weekday=date('w');
$weekdays=['日','月','火','水','木','金','土'];
$weekday_japanese=$weekdays[$weekday];
?>

<?php
if(isset($_SESSION['student_number'])){
    $student_number=$_SESSION['student_number'];
}else{
    $student_number="";
}
if(isset($_SESSION['sports'])){
    $sports=$_SESSION['sports'];
}else{
    $sports="";
}
if(isset($_SESSION['time'])){
    $time=$_SESSION['time'];
}else{
    $time="";
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
            <h1>CIT Sports</h1>
            <div>学籍番号:<?php echo htmlspecialchars($student_number); ?></div>
            <h2>予約完了</h2>

            <?php
                echo "<div>{$today}（{$weekday_japanese}）</div>";
            ?>
            <div>以下の内容で予約しました</div>
            <div>
                <?php echo htmlspecialchars($sports); ?>
            </div>
            <div>
                <?php echo htmlspecialchars($time); ?>
            </div>
            <div class="screen">※この画面は窓口で必要になります。<br>
                　この画面のままにするか、<br>
                　この画面をスクリーンショットで<br>
                　保存してください。
            </div>
            <a href="05table.php">ホームへ戻る</a>
        </div>
    </body>
</html>