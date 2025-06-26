<?php

# HTMLでのエスケープ処理をする関数
function h($var) {
  if (is_array($var)) {
    return array_map('h', $var);
  } else {
    return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
  }
}

// 환경 변수 설정 (PHP 7.0 이상부터 $_ENV는 웹 서버에서 직접 접근하기 어려움. $_SERVER 또는 getenv() 권장)
// 현재 코드에서는 $_ENV 대신 $_SERVER를 사용하고 있으므로,
// $_SERVER['MYSQL_SERVER'] 등이 웹 서버 환경에 설정되어 있지 않다면 기본값 사용됨.
$dbServer = isset($_SERVER['MYSQL_SERVER'])    ? $_SERVER['MYSQL_SERVER']    : '127.0.0.1'; // 이 부분을 $_SERVER로 변경했습니다.
$dbUser = isset($_SERVER['MYSQL_USER'])      ? $_SERVER['MYSQL_USER']    : 'testuser';
$dbPass = isset($_SERVER['MYSQL_PASSWORD'])  ? $_SERVER['MYSQL_PASSWORD'] : 'pass';
$dbName = isset($_SERVER['MYSQL_DB'])       ? $_SERVER['MYSQL_DB']      : 'mydb';

// DSN 문자열 구성
$dsn = "mysql:host={$dbServer};dbname={$dbName};charset=utf8"; // 이곳에서 $dbServer, $dbName 변수를 사용함

try {
  $db = new PDO($dsn, $dbUser, $dbPass);
  $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "Can't connect to the database: " . h($e->getMessage());
  exit(); // 연결 실패 시 스크립트 종료
}

// 학기 번호 함수 등 추가 함수 (이전 코드에서 가져옴)
if (!function_exists('getTermName')) {
    function getTermName($term_num) {
        switch ($term_num) {
            case 1: return '前期';
            case 2: return '後期';
            default: return '不明';
        }
    }
}