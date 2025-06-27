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
// ★あなたの MySQL サーバーのホスト名 (通常は 'localhost' または '127.0.0.1')
$dbServer = isset($_ENV['MYSQL_SERVER'])     ? $_ENV['MYSQL_SERVER']      : 'localhost';
// ★あなたのデータベースユーザー名
$dbUser   = isset($_SERVER['MYSQL_USER'])     ? $_SERVER['MYSQL_USER']     : 'testuser';
// ★あなたのデータベースパスワード
$dbPass   = isset($_SERVER['MYSQL_PASSWORD']) ? $_SERVER['MYSQL_PASSWORD'] : 'pass';
// ★あなたのデータベース名 (スクリーンショットによると 'mydb')
$dbName   = isset($_SERVER['MYSQL_DB'])       ? $_SERVER['MYSQL_DB']       : 'mydb';


// DSN (Data Source Name) 文字列を構築
// データベースの文字コードは 'utf8mb4' を推奨します (絵文字なども含むため)
$dsn = "mysql:host={$dbServer};dbname={$dbName};charset=utf8mb4";


// データベース接続を試みます
try {
    // PDO オブジェクトを $db 変数に割り当てます。
    $db = new PDO($dsn, $dbUser, $dbPass);
    
    // プリペアドステートメントのエミュレーションを無効にします（セキュリティとパフォーマンスのため推奨）
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // エラー発生時に例外をスローするように設定します
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // 結果フェッチのデフォルトモードを連想配列に設定します（カラム名でアクセスしやすくなります）
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // データベース接続に失敗した場合の処理
    // 開発者向けに詳細なエラーメッセージをサーバーログに記録します。
    error_log("データベース接続エラー: " . $e->getMessage());
    // ユーザーには一般的なメッセージを表示し、スクリプトの実行を停止します。
    // この die() が、$db が null インスタンスとして渡されるのを防ぎます。
    die("データベースエラーが発生しました。しばらくしてから再度お試しください。");
}

// ここに getTermName() 関数を定義します。
// 学期番号を学期名に変換する関数
function getTermName($term_num) {
    switch ($term_num) {
        case 1:
            return "前期"; // 1학기 (전기)
        case 2:
            return "後期"; // 2학기 (후기)
        default:
            return "不明"; // 알 수 없는 학기
    }
}

?>