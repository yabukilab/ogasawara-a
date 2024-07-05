<?php
require('db.php');

if (isset($_GET['table']) && isset($_GET['id'])) {
    $table = $_GET['table'];
    $id = $_GET['id'];
    $stmt = $db->prepare("DELETE FROM $table WHERE ID = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: manejiment.php');
exit;
?>
