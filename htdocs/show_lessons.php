<?php
// 세션을 시작합니다.
session_start();
// 데이터베이스 연결 파일을 포함합니다.
require_once 'db.php';

// 응답 헤더를 JSON 형식으로 설정합니다.
header('Content-Type: application/json');

try {
    // category1 필터 값을 GET 요청에서 가져옵니다.
    // 필터가 설정되지 않았거나 '全て' (All)인 경우, 모든 수업을 가져옵니다.
    $category1_filter = $_GET['category1'] ?? '';

    $sql = "SELECT 
                id, 
                name, 
                credit, 
                category1, 
                category2, 
                category3,
                grade          -- 'grade' 컬럼도 함께 조회합니다.
            FROM 
                class";
    $params = [];

    // category1 필터가 유효한 경우 WHERE 절을 추가합니다.
    if (!empty($category1_filter) && $category1_filter !== '全て') {
        $sql .= " WHERE category1 = :category1";
        $params[':category1'] = $category1_filter;
    }

    // 이름순으로 정렬하여 표시합니다.
    $sql .= " ORDER BY name ASC";

    $stmt = $db->prepare($sql);

    // 파라미터가 있으면 바인딩합니다.
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    // PDO::PARAM_STR로 명시적으로 바인딩 타입을 지정할 수도 있습니다 (필요한 경우).
    // 예: $stmt->bindParam(':category1', $category1_filter, PDO::PARAM_STR);

    $stmt->execute();
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 성공적으로 데이터를 가져왔음을 나타내는 JSON 응답을 보냅니다.
    echo json_encode(['status' => 'success', 'lessons' => $lessons]);

} catch (PDOException $e) {
    // データ베이스エラーが発生した場合
    error_log("授業データ読み込みDBエラー: " . $e->getMessage()); // エラーの詳細をサーバーログに記録

    // ユーザーには一般的なエラーメッセージを表示するJSON応答を返します。
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中にデータベースエラーが発生しました。管理者にお問い合わせください。']);
} catch (Exception $e) {
    // その他の予期せぬエラーが発生した場合
    error_log("授業データ読み込み一般エラー: " . $e->getMessage()); // エラーの詳細をサーバーログに記録

    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中に予期せぬエラーが発生しました。']);
}
?>