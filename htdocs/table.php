<!DOCTYPE html>
<html>
<head>
    <title>空き状況</title>
    <link rel="stylesheet" href="table.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: auto;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 15px;
            text-align:center;
            font-size: 12px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<form action="05authenticate.php" method="POST">
    <div class="a">
        <div class="b">
            <p class="CIT">CIT sports</p>
            
            <p class="go">空き状況</p>
    <table>
        <tr>
            <th></th>
            <th>バスケ</th>
            <th>卓球</th>
            <th>スカッシュ</th>
            <th>クライミング</th>
            <th>野球</th>
            <th>テニス</th>
            <th>フットサル</th>
        </tr>
        <tr>
            <td>10:15〜10:45</td>
            <td>データ1</td>
            <td>データ2</td>
            <td>データ3</td>
        </tr>
        <tr>
            <td>10:45〜11:15</td>
            <td>データ4</td>
            <td>データ5</td>
            <td>データ6</td>
        </tr>
    </table>
    </div>
    </div>
    </form>
</body>
</html>