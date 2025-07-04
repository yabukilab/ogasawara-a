<?php
// 세션을 시작합니다.
session_start();
// 데이터베이스 연결 파일을 포함합니다.
require_once 'db.php'; // 이 파일에서 PDO 객체가 $pdo 또는 $db로 정의되어야 합니다.

// 응답 헤더를 JSON 형식으로 설정합니다. UTF-8 인코딩 명시.
header('Content-Type: application/json; charset=UTF-8');

// h() 함수가 db.php에 없거나 다른 공통 파일에 없다면 여기에 추가 (보안 강화를 위해)
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

try {
    // GET 요청에서 필터 값들을 가져옵니다.
    $gradeFilter = $_GET['grade'] ?? '';
    $termFilter = $_GET['term'] ?? '';
    $category1_filter = $_GET['category1'] ?? ''; // 기존 category1 필터도 계속 사용

    $conditions = []; // WHERE 절의 조건들을 저장할 배열
    $params = [];     // 바인딩할 파라미터 값들을 저장할 배열
    $types = '';      // PDO bindParam을 위한 타입 문자열 (i: int, s: string)

    // SQL 쿼리 기본 시작
    // 실제 테이블 이름이 'lessons'인지 'class'인지 확인하고 적절하게 변경하세요.
    // 스크린샷에 'lessons' 데이터가 보이는 것으로 보아 'lessons'일 가능성이 높습니다.
    $sql = "SELECT id, name, credit, category1, category2, category3, grade, term FROM lessons"; // 'term' 컬럼도 조회에 추가

    // 학년 필터 적용
    if (!empty($gradeFilter) && $gradeFilter !== '全て') {
        $conditions[] = "grade = ?";
        $params[] = (int)$gradeFilter; // 정수형으로 캐스팅
        $types .= 'i';
    }

    // 학기 필터 적용
    // 데이터베이스의 'term' 컬럼에 '前期' 또는 '後期'와 같은 값이 저장되어 있다고 가정합니다.
    if (!empty($termFilter) && $termFilter !== '全て') {
        $conditions[] = "term = ?"; // 실제 컬럼명이 'term'이 맞는지 확인하세요.
        $params[] = $termFilter;
        $types .= 's';
    }

    // category1 필터 적용 (기존 로직)
    if (!empty($category1_filter) && $category1_filter !== '全て') {
        $conditions[] = "category1 = ?";
        $params[] = $category1_filter;
        $types .= 's';
    }

    // 조건이 있다면 WHERE 절 추가
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // 이름순으로 정렬하여 표시합니다.
    $sql .= " ORDER BY name ASC";

    // 데이터베이스 연결 객체 확인 ($pdo 또는 $db)
    // 일반적으로 'db.php'에서는 $pdo 변수를 사용합니다.
    // 만약 db.php에서 $db 변수를 사용한다면 $pdo 대신 $db를 사용하세요.
    $stmt = $pdo->prepare($sql); // <-- 여기에 $db 대신 $pdo를 사용했습니다. 확인 필요.

    // 파라미터 바인딩 (동적으로 타입 지정)
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            // $types 문자열에서 해당 파라미터의 타입을 가져와 PDO::PARAM_INT 또는 PDO::PARAM_STR로 바인딩
            $paramType = ($types[$i] === 'i') ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindParam($i + 1, $params[$i], $paramType);
        }
    }

    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 성공적으로 데이터를 가져왔음을 나타내는 JSON 응답을 보냅니다.
    // JSON_UNESCAPED_UNICODE 옵션을 추가하여 일본어 문자가 깨지지 않도록 합니다.
    echo json_encode(['status' => 'success', 'lessons' => $lessons], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // 데이터베이스 에러 발생 시
    error_log("授業データ読み込みDBエラー: " . $e->getMessage()); // 에러 상세를 서버 로그에 기록
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中にデータベースエラーが発生しました。管理者にお問い合わせください。']);
} catch (Exception $e) {
    // 그 외 예상치 못한 에러 발생 시
    error_log("授業データ読み込み一般エラー: " . $e->getMessage()); // 에러 상세를 서버 로그에 기록
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中に予期せぬエラーが発生しました。']);
}
?>