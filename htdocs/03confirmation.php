<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8">
        <title>確認ページ</title>
        <link rel="stylesheet" type="text/css" href="./test.css">
    </head>
    <body>
        <div class="center">
            <h1>CIT Sports</h1>
            <h2>確認画面</h2>

            <form action="" method="post">
                <div>以下の内容でよろしければ登録ボタンを押してください</div>
                <div class="number">学籍番号</div>
                <?php echo htmlspecialchars($student_number); ?>
                <div class="pass">パスワード</div>
                前の情報をpost
                <br>
                <br>
                <div>書き直す</div>
                <input type="submit" value="登録" class="button">
            </form>
        </div>
    </body>
</html>