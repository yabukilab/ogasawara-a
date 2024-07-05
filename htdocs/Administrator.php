<?php
require('db.php'); // データベース接続の設定をインクルードする

  // SQLクエリを定義して実行する
  $stmt = $db->query('SELECT * FROM users');
  
  // 結果を取得する
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
  

    echo '<table border="1">';
    echo '<tr><th>ID</th><th>Student Number</th><th>Password Hash</th><th>Created At</th></tr>';
    
    foreach ($users as $user) {
      echo '<tr>';
      echo '<td>' . h($user['id']) . '</td>';
      echo '<td>' . h($user['student_number']) . '</td>';
      echo '<td>' . h($user['password_hash']) . '</td>';
      echo '<td>' . h($user['created_at']) . '</td>';
      echo '</tr>';
    }
?>
