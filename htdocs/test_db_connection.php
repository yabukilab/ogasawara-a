<?php
require_once 'db.php'; // db.php 파일 포함
if (isset($db) && $db instanceof PDO) {
    echo "データベースに正常に接続されました。"; // 데이터베이스에 정상적으로 연결되었습니다.
    try {
        // 더미 쿼리 실행으로 연결 확인
        $stmt = $db->query("SELECT 1");
        if ($stmt) {
            echo " クエリも実行可能です。"; // 쿼리도 실행 가능합니다.
        }
    } catch (PDOException $e) {
        echo " しかし、クエリ実行中にエラー: " . h($e->getMessage()); // 그러나 쿼리 실행 중 오류:
    }
} else {
    echo "データベースへの接続に失敗しました。db.phpの設定を確認してください。"; // 데이터베이스 연결에 실패했습니다. db.php 설정을 확인하십시오.
}
?>