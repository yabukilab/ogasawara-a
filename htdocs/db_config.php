<?php

# HTMLでのエスケープ処理をする関数（データベースとは無関係だが，ついでにここで定義しておく．）
function h($var) {
  if (is_array($var)) {
    return array_map('h', $var);
  } else {
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
  }
}

$dbServer = isset($_ENV['MYSQL_SERVER'])    ? $_ENV['MYSQL_SERVER']      : '127.0.0.1';
$dbUser = isset($_SERVER['MYSQL_USER'])     ? $_SERVER['MYSQL_USER']     : 'testuser';
$dbPass = isset($_SERVER['MYSQL_PASSWORD']) ? $_SERVER['MYSQL_PASSWORD'] : 'pass';
$dbName = isset($_SERVER['MYSQL_DB'])       ? $_SERVER['MYSQL_DB']       : 'mydb';


$dsn = "mysql:host={$dbServer};dbname={$dbName};charset=utf8";


try {
  $db = new PDO($dsn, $dbUser, $dbPass);

  # プリペアドステートメントのエミュレーションを無効にする．
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  # エラー→例外
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  # 結果フェッチのデフォルトモードを連想配列に設定します (カラム名でアクセスしやすくなります)
  $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  echo "Can't connect to the database: " . h($e->getMessage());
}

function getTerName($term_num) {
  swich ($term_num) {
    case 1:
      return "前期"; // 1学期(前期)
    case 2:
      return "後期"; // 2学期(後期)
    default:
      return "不明"; // 知らない学期
  }
}

?>