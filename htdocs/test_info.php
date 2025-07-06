<?php
session_start();
echo "현재 세션 user_id: " . ($_SESSION['user_id'] ?? '없음') . "<br>";
phpinfo();
?>