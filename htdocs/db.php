<?php

// h() 함수는 HTMLエスケープ処理を行うためのもので、ここではデータベースとは直接関係ないが、
// db.phpが他のスクリプトでincludeされることを考慮し、ここで定義する。
if (!function_exists('h')) {
    function h($var) {
        if (is_array($var)) {
            return array_map('h', $var);
        } else {
            return htmlspecialchars($var, ENT_QUOTES, 'UTF-8');
        }
    }
}

// === 중요: 아래 DB 연결 정보를 실제 사용 중인 환경에 맞게 수정하세요! ===
// 1. $dbServer: 데이터베이스 서버 주소 (로컬: '127.0.0.1' 또는 'localhost', 호스팅: 호스팅 업체 제공 주소)
// 2. $dbUser: 데이터베이스 사용자 이름 (예: 'root', 'your_user')
// 3. $dbPass: 데이터베이스 비밀번호 (예: XAMPP 기본 'root'는 보통 빈 문자열 '')
// 4. $dbName: 데이터베이스 이름 (스크린샷에선 'mydb')
$dbServer = isset($_ENV['MYSQL_SERVER']) ? $_ENV['MYSQL_SERVER'] : '127.0.0.1'; // 보통 로컬 환경이면 '127.0.0.1' 또는 'localhost'
$dbUser = isset($_SERVER['MYSQL_USER']) ? $_SERVER['MYSQL_USER'] : 'root'; // <-- **가장 흔한 로컬 DB 사용자 이름**
$dbPass = isset($_SERVER['MYSQL_PASSWORD']) ? $_SERVER['MYSQL_PASSWORD'] : ''; // <-- **비밀번호가 없는 경우가 많음**
$dbName = isset($_SERVER['MYSQL_DB']) ? $_ENV['MYSQL_DB'] : 'mydb'; // <-- 스크린샷에서 'mydb' 확인

$dsn = "mysql:host={$dbServer};dbname={$dbName};charset=utf8";

try {
    $db = new PDO($dsn, $dbUser, $dbPass);
    // プリペアドステートメントのエミュレーションを無効にする．
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    // エラー→例外
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "DB接続成功！"; // 디버그용: 성공 시 메시지 출력 (실제 운영 시에는 주석 처리 또는 삭제)

} catch (PDOException $e) {
    // 데이터베이스 연결 실패 시 JSON 형식으로 에러 메시지 반환
    // 클라이언트 (JavaScript)가 이를 JSON으로 파싱할 수 있도록 함
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'error',
        'message' => 'データベース接続エラーが発生しました。',
        'detail' => $e->getMessage() // 실제 에러 메시지를 포함하여 디버깅에 도움
    ]);
    exit(); // 중요: 연결 실패 시 스크립트 즉시 중단
}

// 데이터베이스 연결이 성공한 경우, 이 파일은 $db 객체를 다른 스크립트(예: get_timetable.php)에 제공합니다.
// 성공 시에는 아무것도 출력하지 않습니다.
?>