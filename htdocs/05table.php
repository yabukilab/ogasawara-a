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

            <div>○月×日（△）（当日の日にち）</div>
            <div class="hyou">
                <table>
                    <thead>
                        <tr>
                            <th>施設/時間　　　　　</th>
                            <th>10:15-10:45</th>
                            <th>10:45-11:15</th>
                            <th>11:15-11:45</th>
                            <th>11:45-12:15</th>
                            <th>12:45-13:15</th>
                            <th>13:15-13:45</th>
                            <th>13:45-14:15</th>
                            <th>14:15-14:45</th>
                            <th>14:45-15:15</th>
                            <th>15:15-15:45</th>
                            <th>15:45-16:15</th>
                            <th>16:15-16:45</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php
                        for($i=0; $i<7; $i++){
                            if($i==0){
                                $sports="卓球";
                            }elseif($i==1){
                                $sports="バスケ";
                            }elseif($i==2){
                                $sports="スカッシュ";
                            }elseif($i==3){
                                $sports="クライミング";
                            }elseif($i==4){
                                $sports="テニス";
                            }elseif($i==5){
                                $sports="野球";
                            }elseif($i==6){
                                $sports="サッカー";
                            }

                            print("<tr>
                            <th>{$sports}</th>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>");
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </body>
</html>