<?php
session_start();
require_once 'db_config.php'; // DB設定を読み込みます

header('Content-Type: application/json'); // JSONレスポンスを指定

$response = ['success' => false, 'message' => ''];

// ユーザーがログインしているか (user_id がセッションにあるか) 確認
if (!isset($_SESSION['user_id'])) { // student_number から user_id に変更
    $response['message'] = 'ログインしていません。';
    echo json_encode($response);
    exit();
}

// セッションから user_id を取得
$user_id = $_SESSION['user_id']; // student_number から user_id に変更

// POSTされたJSONデータを取得
$input_data = file_get_contents('php://input');
$timetable_data = json_decode($input_data, true);

// JSONデコードエラーチェック
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = '無効なJSONデータです。';
    $response['error_details'] = json_last_error_msg();
    echo json_encode($response);
    exit();
}

// 登録する授業がない場合のエラーメッセージ（空の配列が送られてきた場合）
if (empty($timetable_data)) {
    // クライアント側で「登録する授業がありません」とアラートを出すのが望ましいため、
    // ここでエラーを返すのは適切な処理です。
    $response['message'] = '登録する授業がありません。';
    echo json_encode($response);
    exit();
}

try {
    $db->beginTransaction(); // トランザクションを開始

    // 現在の学年の全ての既存時間割データを削除
    // 送られてきたデータは全て同じ学年のものと仮定し、最初のデータから学年を取得
    $grade_to_delete = $timetable_data[0]['grade'];
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade"); // student_number から user_id に変更
    $stmt->execute([':user_id' => $user_id, ':grade' => $grade_to_delete]); // student_number から user_id に変更

    // 新しい時間割データを挿入
    // user_id を使用し、is_primary も含めます
    $stmt = $db->prepare("INSERT INTO user_timetables (user_id, grade, day, period, class_id, is_primary) VALUES (:user_id, :grade, :day, :period, :class_id, :is_primary)"); // student_number から user_id に変更

    foreach ($timetable_data as $entry) {
        $stmt->execute([
            ':user_id' => $user_id, // student_number から user_id に変更
            ':grade' => $entry['grade'],
            ':day' => $entry['day'],
            ':period' => $entry['period'],
            ':class_id' => $entry['class_id'],
            ':is_primary' => $entry['is_primary'] // JavaScriptから送信される is_primary 値 (通常は 0)
        ]);
    }

    $db->commit(); // トランザクションをコミット (PDOオブジェクトが $db なので修正)
    $response['success'] = true;
    $response['message'] = '時間割が正常に登録されました。';

} catch (PDOException $e) {
    $db->rollBack(); // エラー時はロールバック
    $response['message'] = '時間割の登録に失敗しました。';
    $response['error_details'] = $e->getMessage();
    error_log("Timetable save failed: " . $e->getMessage()); // エラーログに詳細を記録
}

echo json_encode($response);
?>