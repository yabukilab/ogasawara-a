<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授業一覧 (Class List)</title>
</head>
<body>
    <h1>授業一覧 (Class List)</h1>

    <?php
    // データベース接続情報 (제공해주신 정보로 업데이트)
        $dbServer = '127.0.0.1';
        $dbName = 'mydb';
        $dbUser = 'testuser';
        $dbPass = 'pass';            // 비밀번호는 빈 문자열 ''

    // データベース接続
    try {
        $pdo = new PDO("mysql:host=$dbServer;dbname=$dbName;charset=utf8", $dbuser, $dbpass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // 'class' テーブルから指定されたカラムのデータを取得
        // class 테이블에 존재하는 컬럼은 id, grade, term, name, category1, category2, category3, credit 입니다.
        $sql = "SELECT 
                    id, grade, term, name, category1, category2, category3, credit 
                FROM class";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 結果が0件の場合
        if (empty($classes)) {
            echo "<p>登録されている授業がありません。</p>";
            // 만약 'class' 테이블에 데이터를 추가하는 HTML 폼이 있다면, 그 폼으로의 링크를 여기에 추가할 수 있습니다.
            // 예시: echo "<a href='register_class_form.html'>新しい授業を登録する</a>";
        } else {
            // 表形式で出力
            echo "<table border='1'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>ID</th>";
            echo "<th>学年 (Grade)</th>";
            echo "<th>学期 (Term)</th>";
            echo "<th>授業名 (Name)</th>";
            echo "<th>分類1 (Category1)</th>";
            echo "<th>分類2 (Category2)</th>";
            echo "<th>分類3 (Category3)</th>";
            echo "<th>単位 (Credit)</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";

            // 各行のデータを表示
            foreach ($classes as $class) {
                echo "<tr>";
                // htmlspecialchars() でHTML特殊文字をエスケープしてXSS対策
                echo "<td>" . htmlspecialchars($class['id']) . "</td>";
                echo "<td>" . htmlspecialchars($class['grade']) . "</td>";
                echo "<td>" . htmlspecialchars($class['term']) . "</td>";
                echo "<td>" . htmlspecialchars($class['name']) . "</td>";
                echo "<td>" . htmlspecialchars($class['category1'] ?? '') . "</td>"; // NULL 가능성 대비
                echo "<td>" . htmlspecialchars($class['category2'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($class['category3'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($class['credit']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
        }

    } catch (PDOException $e) {
        // エラー発生時の表示
        echo "<p style='color: red;'>エラーが発生しました: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
    <br>
    </body>
</html>