<?php
// PHP 에러를 화면에 표시 (개발 환경에서만 사용, 실제 서비스에서는 비활성화하거나 로그 파일로만 기록)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 세션을 시작합니다.
session_start();

// 데이터베이스 연결 파일을 포함합니다.
// 이 파일(db.php)에서 PDO 객체가 $db 변수에 할당됩니다.
require_once 'db.php';

// 응답 헤더를 JSON 형식으로 설정합니다. UTF-8 인코딩을 명시합니다.
header('Content-Type: application/json; charset=UTF-8');

// h() 함수는 db.php에 정의되어 있으므로 여기서 다시 정의할 필요는 없습니다.
// 하지만 db.php가 include 되지 않는 예외 상황에 대비하여 안전하게 다시 정의하고 싶다면 유지해도 무방합니다.
/*
if (!function_exists('h')) {
    function h($str) {
        if (is_array($str)) {
            return array_map('h', $str);
        } else {
            return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
        }
    }
}
*/

try {
    // GET 요청에서 필터 값들을 가져옵니다.
    // '?? '' ' 구문을 사용하여 값이 없으면 빈 문자열로 초기화합니다.
    $gradeFilter = $_GET['grade'] ?? '';
    $termFilter = $_GET['term'] ?? '';
    $category1_filter = $_GET['category1'] ?? ''; // 기존 category1 필터도 계속 사용

    $conditions = []; // SQL WHERE 절의 조건들을 저장할 배열
    $params = [];     // PDO 바인딩할 파라미터 값들을 저장할 배열
    $types = '';      // PDO bindParam을 위한 타입 문자열 (i: integer, s: string)

    // SQL 쿼리 기본 시작
    // 테이블 이름을 'class'로 변경했습니다.
    $sql = "SELECT id, name, credit, category1, category2, category3, grade, term FROM class"; 

    // 학년 필터 적용: '全て' (All) 옵션이 선택되지 않았을 때만 필터링합니다.
    if (!empty($gradeFilter) && $gradeFilter !== '全て') {
        $conditions[] = "grade = ?"; // 학년 필터 조건 추가
        $params[] = (int)$gradeFilter; // 학년 값은 정수형으로 바인딩
        $types .= 'i'; // 타입은 정수 (integer)
    }

    // 학기 필터 적용: '全て' (All) 옵션이 선택되지 않았을 때만 필터링합니다.
    // index.php에서 '前期', '後期' 문자열이 전송되므로, 이를 int 타입에 맞게 변환합니다.
    if (!empty($termFilter) && $termFilter !== '全て') {
        $conditions[] = "term = ?"; // 학기 필터 조건 추가
        // '前期'는 1로, '後期'는 2로 매핑 (class 테이블의 term 컬럼이 int 타입이므로)
        if ($termFilter === '前期') {
            $params[] = 1; 
        } elseif ($termFilter === '後期') {
            $params[] = 2;
        } else {
            // 예상치 못한 값이 들어왔을 경우, 이 조건은 무시하거나 에러 로깅 가능
            // 현재 index.php에서는 '全て', '前期', '後期'만 전송하므로 이 else 블록은 실행되지 않을 것입니다.
        }
        $types .= 'i'; // 타입은 정수 (integer)
    }

    // category1 필터 적용 (기존 로직 유지): '全て' (All) 옵션이 선택되지 않았을 때만 필터링합니다.
    if (!empty($category1_filter) && $category1_filter !== '全て') {
        $conditions[] = "category1 = ?"; // 카테고리1 필터 조건 추가
        $params[] = $category1_filter; // 카테고리1 값은 문자열로 바인딩
        $types .= 's'; // 타입은 문자열 (string)
    }

    // 모든 조건들을 AND로 연결하여 WHERE 절을 구성합니다.
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // 수업 목록을 이름순으로 정렬하여 표시합니다.
    $sql .= " ORDER BY name ASC";

    // 디버깅을 위해 최종 SQL 쿼리와 파라미터를 서버 에러 로그에 기록합니다.
    error_log("DEBUG show_lessons.php: SQL Query: " . $sql);
    error_log("DEBUG show_lessons.php: SQL Params: " . print_r($params, true));
    error_log("DEBUG show_lessons.php: SQL Types: " . $types);

    // 데이터베이스 연결 객체는 db.php에서 $db 변수에 할당되므로, $db->prepare($sql)을 사용합니다.
    $stmt = $db->prepare($sql);

    // 파라미터가 있다면 동적으로 타입에 맞게 바인딩합니다.
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $paramType = ($types[$i] === 'i') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($i + 1, $params[$i], $paramType);
        }
    }

    // SQL 쿼리를 실행합니다.
    $stmt->execute();
    // 실행 결과를 연관 배열 형태로 모두 가져옵니다.
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 성공적으로 데이터를 가져왔음을 나타내는 JSON 응답을 보냅니다.
    // JSON_UNESCAPED_UNICODE 옵션을 추가하여 일본어와 같은 유니코드 문자가 깨지지 않고 올바르게 표시되도록 합니다.
    echo json_encode(['status' => 'success', 'lessons' => $lessons], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // 데이터베이스 관련 에러가 발생한 경우
    // 에러 상세 메시지를 서버 로그에 기록합니다. (관리자만 볼 수 있음)
    error_log("授業データ読み込みDBエラー (show_lessons.php): " . $e->getMessage());

    // 사용자에게는 일반적인 에러 메시지를 JSON 형태로 반환합니다.
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中にデータベースエラーが発生しました。管理者にお問い合わせください。']);
} catch (Exception $e) {
    // 그 외 예상치 못한 일반 에러가 발생한 경우
    // 에러 상세 메시지를 서버 로그에 기록합니다.
    error_log("授業データ読み込み一般エラー (show_lessons.php): " . $e->getMessage());

    // 사용자에게 일반적인 에러 메시지를 JSON 형태로 반환합니다.
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中に予期せぬエラーが発生しました。']);
}
?>