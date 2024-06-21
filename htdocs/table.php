<!DOCTYPE html>
<html>
<head>
    <title>タイムスロットの表</title>
    <link rel="stylesheet" href="table.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
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
    <div class="A">
        <div class="B">
            <p class="CIT">CIT sports</p>
            
            <p class="logina">ログイン</p>
    <h2>タイムスロットの表</h2>
    <table>
        <tr>
            <th></th>
            <th>バスケ</th>
            <th>卓球</th>
            <th>スカッシュ</th>
        </tr>
        <tr>
            <td>10:00〜10:30</td>
            <td>データ1</td>
            <td>データ2</td>
            <td>データ3</td>
        </tr>
        <tr>
            <td>10:30〜11:00</td>
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