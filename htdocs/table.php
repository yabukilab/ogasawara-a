<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>空き状況確認</title>
    <link rel="stylesheet" href="login.css">
    <style>
        table {
            width: 50%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
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
            
            <p class="go">空き状況</p>
<table>
    <thead>
        <tr>
            <th>空き状況</th>
            <th>バスケ</th>
            <th>スカッシュ</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>山田太郎</td>
            <td>30</td>
            <td>エンジニア</td>
        </tr>
        <tr>
            <td>鈴木花子</td>
            <td>25</td>
            <td>デザイナー</td>
        </tr>
        <tr>
            <td>田中一郎</td>
            <td>40</td>
            <td>マネージャー</td>
        </tr>
    </tbody>
</table>
      </div>
      </div>
</form>

</body>
</html>