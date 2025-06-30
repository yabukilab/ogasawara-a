<?php
session_start();
require_once 'db_config.php'; // DB設定を読み込みます

header('Content-Type: application/json'); // JSONレスポンスを指定

$response = ['success' => false, 'message' => ''];

// ユーザーがログインしているか (user_id がセッションにあるか) 確認
if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'ログインしていません。';
    echo json_encode($response);
    exit();
}

// セッションから user_id を取得
$user_id = $_SESSION['user_id'];

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
    $response['message'] = '登録する授業がありません。';
    echo json_encode($response);
    exit();
}

try {
    $db->beginTransaction(); // トランザクションを開始

    // 現在の学年の全ての既存時間割データを削除
    // 送られてきたデータは全て同じ学年のものと仮定し、最初のデータから学年を取得
    $grade_to_delete = $timetable_data[0]['grade']; // 학년은 반드시 존재한다고 가정
    
    // user_id와 grade를 기반으로 이전 시간표 삭제
    $stmt = $db->prepare("DELETE FROM user_timetables WHERE user_id = :user_id AND grade = :grade");
    $stmt->execute([':user_id' => $user_id, ':grade' => $grade_to_delete]);

    // 新しい時間割データを挿入
    // is_primary 컬럼을 사용하지 않거나, 있다면 default 값을 사용하도록 합니다.
    // 여기서는 is_primary 컬럼이 아예 없거나, DEFAULT 0으로 설정되어 있다고 가정합니다.
    $stmt = $db->prepare("INSERT INTO user_timetables (user_id, grade, day, period, class_id) VALUES (:user_id, :grade, :day, :period, :class_id)");

    foreach ($timetable_data as $entry) {
        // 필수 필드 유효성 검사
        if (!isset($entry['grade'], $entry['day'], $entry['period'], $entry['class_id'])) {
            throw new Exception("時間割データに不足しているフィールドがあります。");
        }

        $stmt->execute([
            ':user_id' => $user_id,
            ':grade' => $entry['grade'],
            ':day' => $entry['day'],
            ':period' => $entry['period'],
            ':class_id' => $entry['class_id']
            // ':is_primary' => $entry['is_primary'] // JavaScript에서 is_primary를 보내지 않으므로 주석 처리
        ]);
    }

    $db->commit();
    $response['success'] = true;
    $response['message'] = '時間割が正常に登録されました。';

} catch (PDOException $e) {
    $db->rollBack();
    $response['message'] = 'データベースエラーにより時間割の登録に失敗しました。';
    $response['error_details'] = $e->getMessage();
    error_log("Timetable save failed PDOException: " . $e->getMessage()); // 에러 로그에 PDO 에러 상세 기록
} catch (Exception $e) {
    $db->rollBack();
    $response['message'] = '時間割の登録に失敗しました。';
    $response['error_details'] = $e->getMessage();
    error_log("Timetable save failed Exception: " . $e->getMessage()); // 일반 에러 상세 기록
}

echo json_encode($response);
?>