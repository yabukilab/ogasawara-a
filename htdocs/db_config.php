<?php

# HTMLでのエスケープ処理をする関数
function h($var) {
    if (is_array($var)) {
        return array_map('h', $var);
    } else {
        return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
    }
}

// 環境変数またはデフォルト値を使用（本番環境では環境変数を設定推奨）
// デフォルト値は実際のデータベース情報に合わせてください
$dbServer = isset($_ENV['MYSQL_SERVER'])    ? $_ENV['MYSQL_SERVER']      : '127.0.0.1';
$dbUser = isset($_SERVER['MYSQL_USER'])     ? $_SERVER['MYSQL_USER']     : 'testuser';
$dbPass = isset($_SERVER['MYSQL_PASSWORD']) ? $_SERVER['MYSQL_PASSWORD'] : 'pass';
$dbName = isset($_SERVER['MYSQL_DB'])       ? $_SERVER['MYSQL_DB']       : 'mydb';


// DSN (Data Source Name) 文字列を構築
$dsn = "mysql:host={$dbServer};dbname={$dbName};charset=utf8mb4"; // 文字コードは utf8mb4 を推奨

// データベース接続を試みます
try {
    // PDO オブジェクトを $db 変数に割り当てます。これが login.php で使われる変数名です。
    $db = new PDO($dsn, $dbUser, $dbPass);
    
    // プリペアドステートメントのエミュレーションを無効にします（セキュリティとパフォーマンスのため推奨）
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // エラー発生時に例外をスローするように設定します
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 結果フェッチのデフォルトモードを連想配列に設定します（カラム名でアクセスしやすくなります）
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // データベース接続に失敗した場合の処理
    // 開発者向けに詳細なエラーメッセージをサーバーログに記録します。
    error_log("データベース接続エラー: " . $e->getMessage());
    // ユーザーには一般的なメッセージを表示し、スクリプトの実行を停止します。
    die("データベースエラーが発生しました。しばらくしてから再度お試しください。");
}

// 必要であれば、getTermName などの関数もここに定義できます。
// 例:
// function getTermName($term_num) {
//     switch ($term_num) {
//         case 1: return "前期";
//         case 2: return "後期";
//         default: return "不明";
//     }
// }

?>