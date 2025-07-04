<?php
// PHP エラーを画面に表示 (開発環境で**のみ**使用、実際のサービスでは無効化するかログファイルのみに記録)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// セッションを開始します。
session_start();

// データベース接続ファイルをインクルードします。
// このファイル(db.php)で PDO オブジェクトが $db 変数に割り当てられます。
require_once 'db.php';

// 応答ヘッダーを JSON 形式に設定します。UTF-8 エンコーディングを明示します。
header('Content-Type: application/json; charset=UTF-8');

try {
    // GET リクエストからフィルター値を取得します。
    // '?? '' ' 構文を使用して値がなければ空文字列で初期化します。
    $gradeFilter = $_GET['grade'] ?? '';
    $termFilter = $_GET['term'] ?? '';
    $category1_filter = $_GET['category1'] ?? '';

    $conditions = []; // SQL WHERE 節の条件を保存する配列
    $params = [];     // PDO バインドするパラメーター値を保存する配列
    $param_types = []; // 各パラメーターの PDO::PARAM_* タイプを保存する配列

    // SQL クエリの基本開始
    $sql = "SELECT id, name, credit, category1, category2, category3, grade, term FROM class";

    // 学年フィルター適用: '全て' (All) オプションが選択されていない場合のみフィルターします。
    if (!empty($gradeFilter) && $gradeFilter !== '全て') {
        $conditions[] = "grade = ?"; // 学年フィルター条件追加
        $params[] = (int)$gradeFilter; // 学年値は整数型にキャスト
        $param_types[] = PDO::PARAM_INT; // タイプは整数
    }

    // 学期フィルター適用: '全て' (All) オプションが選択されていない場合のみフィルターします。
    // class テーブルの term カラムが VARCHAR(20) なので、文字列として比較します。
    if (!empty($termFilter) && $termFilter !== '全て') {
        $conditions[] = "term = ?"; // 学期フィルター条件追加
        $params[] = $termFilter; // 学期値は文字列としてそのままバインド
        $param_types[] = PDO::PARAM_STR; // タイプは文字列
    }

    // category1 フィルター適用: '全て' (All) オプションが選択されていない場合のみフィルターします。
    if (!empty($category1_filter) && $category1_filter !== '全て') {
        $conditions[] = "category1 = ?"; // カテゴリー1 フィルター条件追加
        $params[] = $category1_filter; // カテゴリー1 値は文字列としてバインド
        $param_types[] = PDO::PARAM_STR; // タイプは文字列
    }

    // 全ての条件を AND で結合して WHERE 節を構成します。
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    // 授業リストを名前順でソートして表示します。
    $sql .= " ORDER BY name ASC";

    // デバッグのため最終的な SQL クエリとパラメーターをサーバーのエラーログに記録します。
    error_log("DEBUG show_lessons.php: SQL Query: " . $sql);
    error_log("DEBUG show_lessons.php: SQL Params: " . print_r($params, true));
    error_log("DEBUG show_lessons.php: SQL Param Types: " . print_r($param_types, true));

    // データベース接続オブジェクトは db.php で $db 変数に割り当てられているため、$db->prepare($sql) を使用します。
    $stmt = $db->prepare($sql);

    // パラメーターがある場合、動的にタイプに合わせてバインドします。
    if (!empty($params)) {
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindParam($i + 1, $params[$i], $param_types[$i]);
        }
    }

    // SQL クエリを実行します。
    $stmt->execute();
    // 実行結果を連想配列の形式で全て取得します。
    $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 正常にデータを取得したことを示す JSON 応答を送信します。
    // JSON_UNESCAPED_UNICODE オプションを追加して、日本語などのユニコード文字が正しく表示されるようにします。
    echo json_encode(['status' => 'success', 'lessons' => $lessons], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    // データベース関連のエラーが発生した場合
    // エラー詳細メッセージをサーバーログに記録します。
    error_log("授業データ読み込みDBエラー (show_lessons.php): " . $e->getMessage());

    // ユーザーには一般的なエラーメッセージを JSON 形式で返します。
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中にデータベースエラーが発生しました。管理者にお問い合わせください。'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // その他の予期せぬ一般的なエラーが発生した場合
    // エラー詳細メッセージをサーバーログに記録します。
    error_log("授業データ読み込み一般エラー (show_lessons.php): " . $e->getMessage());

    // ユーザーには一般的なエラーメッセージを JSON 形式で返します。
    echo json_encode(['status' => 'error', 'message' => '授業データの読み込み中に予期せぬエラーが発生しました。'], JSON_UNESCAPED_UNICODE);
}
?>