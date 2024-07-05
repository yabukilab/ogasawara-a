<?php
require('db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table = $_POST['table'];
    if ($table === 'reservations') {
        $stmt = $db->prepare("INSERT INTO reservations (facility, time, reserver, date) VALUES (:facility, :time, :reserver, :date)");
        $stmt->execute([
            ':facility' => $_POST['facility'],
            ':time' => $_POST['time'],
            ':reserver' => $_POST['reserver'],
            ':date' => $_POST['date']
        ]);
    } elseif ($table === 'users') {
        $stmt = $db->prepare("INSERT INTO users (student_number, password_hash, created_at) VALUES (:student_number, :password_hash, :created_at)");
        $stmt->execute([
            ':student_number' => $_POST['student_number'],
            ':password_hash' => password_hash($_POST['password'], PASSWORD_BCRYPT),
            ':created_at' => date('Y-m-d H:i:s')
        ]);
    }
    header('Location: manejiment.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>Add New Entry</title>
</head>
<body>
    <h1>Add New Entry</h1>
    <form action="add.php" method="post">
        <input type="hidden" name="table" value="<?php echo h($_GET['table']); ?>">
        <?php if ($_GET['table'] === 'reservations'): ?>
            <label for="facility">Facility:</label>
            <input type="text" name="facility" id="facility" required>
            <br>
            <label for="time">Time:</label>
            <input type="text" name="time" id="time" required>
            <br>
            <label for="reserver">Reserver:</label>
            <input type="text" name="reserver" id="reserver" required>
            <br>
            <label for="date">Date:</label>
            <input type="date" name="date" id="date" required>
        <?php elseif ($_GET['table'] === 'users'): ?>
            <label for="student_number">Student Number:</label>
            <input type="text" name="student_number" id="student_number" required>
            <br>
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        <?php endif; ?>
        <br>
        <button type="submit">Add</button>
    </form>
    <a href="manejiment.php">Back to Tables</a>
</body>
</html>
