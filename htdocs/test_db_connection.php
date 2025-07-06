<?php
require_once 'db.php'; // db.php 파일을 포함

// db.php에서 PDO 객체인 $db가 성공적으로 생성되었다면, 이 코드는 실행됩니다.
// 오류가 발생했다면, db.php에서 이미 JSON을 출력하고 exit() 했을 것입니다.

// 간단한 데이터베이스 쿼리를 실행하여 연결이 잘 되었는지 확인
try {
    $stmt = $db->query("SELECT 1"); // 단순히 '1'을 선택하여 DB 연결을 테스트
    $result = $stmt->fetchColumn();

    if ($result == 1) {
        echo "<h1>データベース接続に成功しました！🎉</h1>";
        echo "<p>これで時間割のデータも読み込めるはずです。</p>";
        echo "<p><a href=\"index.php\">時間割ページに戻る</a></p>";
    } else {
        echo "<h1>データベース接続テストに失敗しました... 😞</h1>";
        echo "<p>予期しない結果が返されました。</p>";
        echo "<p><a href=\"index.php\">時間割ページに戻る</a></p>";
    }
} catch (PDOException $e) {
    // db.php에서 이미 에러를 처리하고 exit() 했어야 하지만,
    // 혹시 모를 경우를 대비하여 추가적인 에러 핸들링
    echo "<h1>クエリ実行中にエラーが発生しました。</h1>";
    echo "<p>詳細: " . h($e->getMessage()) . "</p>";
    echo "<p><a href=\"index.php\">時間割ページに戻る</a></p>";
}
?>