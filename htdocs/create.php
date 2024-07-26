<!--create.php-->

<?php
session_start();
require('db.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $db->prepare('INSERT INTO reservations (facility, time, reserver, date) VALUES (:facility, :time, :reserver, :date)');
        $stmt->bindParam(':facility', $_POST['sports']);
        $stmt->bindParam(':time', $_POST['time']);
        $stmt->bindParam(':reserver', $_POST['student_number']);
        $stmt->bindParam(':date', $_POST['date']);
        $stmt->execute();

        $_SESSION['student_number']=$_POST['student_number'];
        $_SESSION['sports']=$_POST['sports'];
        $_SESSION['time']=$_POST['time'];
        header('Location: 07reserved.php');
        exit();
    } catch (PDOException $e) {
        header('Location: 06reservation.php?error=err');
        exit();
    }
} else {
    header('Location: 06reservation.php?error=err');
    exit();
}
?>