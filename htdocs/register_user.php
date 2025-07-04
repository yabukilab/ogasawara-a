<?php
// 세션을 시작합니다. 스크립트의 맨 위에서 항상 호출되어야 합니다.
session_start();

// db.php 파일을 포함합니다. 이 파일은 데이터베이스 연결($db)과 h() 함수를 제공합니다.
require_once 'db.php';

// 에러 또는 성공 메시지를 저장할 변수를 초기화합니다.
$message = '';
$message_type = ''; // 'success' 또는 'error'

// HTTP 요청이 POST 메서드인지 확인합니다. 즉, 회원가입 폼이 제출되었는지 확인합니다.
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 사용자로부터 입력값을 가져와 h() 함수로 안전하게 만듭니다.
    // 'student_number'를 사용하도록 변경
    $student_number = h($_POST['student_number'] ?? '');
    $email = h($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $department = h($_POST['department'] ?? ''); // 학과 정보 추가

    // 모든 필수 필드가 채워졌는지 확인합니다.
    if (empty($student_number) || empty($email) || empty($password) || empty($confirm_password) || empty($department)) {
        $message = "모든 필드를 입력해주세요.";
        $message_type = "error";
    } elseif ($password !== $confirm_password) {
        // 비밀번호와 비밀번호 확인이 일치하는지 확인합니다.
        $message = "비밀번호가 일치하지 않습니다.";
        $message_type = "error";
    } elseif (strlen($password) < 6) {
        // 비밀번호 최소 길이 확인 (예시: 6자 이상)
        $message = "비밀번호는 최소 6자 이상이어야 합니다.";
        $message_type = "error";
    } else {
        try {
            // 학번이 이미 존재하는지 확인합니다.
            $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE student_number = :student_number");
            $stmt->bindParam(':student_number', $student_number);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $message = "이미 존재하는 학번입니다. 다른 학번을 사용해주세요.";
                $message_type = "error";
            } else {
                // 이메일이 이미 존재하는지 확인합니다.
                $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $message = "이미 존재하는 이메일입니다. 다른 이메일을 사용해주세요.";
                    $message_type = "error";
                } else {
                    // 비밀번호를 해시화합니다. (매우 중요!)
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // 사용자 정보를 데이터베이스에 삽입합니다.
                    $stmt = $db->prepare("INSERT INTO users (student_number, email, password, department) VALUES (:student_number, :email, :password, :department)");
                    $stmt->bindParam(':student_number', $student_number);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':password', $hashed_password);
                    $stmt->bindParam(':department', $department);

                    if ($stmt->execute()) {
                        $message = "회원가입이 성공적으로 완료되었습니다. 이제 로그인할 수 있습니다.";
                        $message_type = "success";
                        // 회원가입 성공 후 로그인 페이지로 리다이렉트할 수도 있습니다.
                        // header("Location: login.php");
                        // exit();
                    } else {
                        $message = "회원가입 중 오류가 발생했습니다. 다시 시도해주세요.";
                        $message_type = "error";
                    }
                }
            }
        } catch (PDOException $e) {
            // 데이터베이스 관련 에러가 발생한 경우
            error_log("Register DB Error: " . $e->getMessage()); // 에러 상세 내용을 서버 로그에 기록
            $message = "데이터베이스 오류가 발생했습니다. 잠시 후 다시 시도해주세요.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 (Register)</title>
    <link rel="stylesheet" href="style2.css"> </head>
<body>
    <div class="auth-container">
        <h1>회원가입</h1>
        <?php if (!empty($message)): ?>
            <p class="message <?php echo $message_type; ?>"><?php echo h($message); ?></p>
        <?php endif; ?>
        <form action="register_user.php" method="post" class="auth-form">
            <label for="student_number">학번:</label> <input type="text" id="student_number" name="student_number" required value="<?php echo h($student_number ?? ''); ?>">

            <label for="email">이메일:</label>
            <input type="email" id="email" name="email" required value="<?php echo h($email ?? ''); ?>">

            <label for="password">비밀번호:</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">비밀번호 확인:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>

            <label for="department">학과:</label>
            <input type="text" id="department" name="department" required value="<?php echo h($department ?? ''); ?>">

            <button type="submit">회원가입</button>
        </form>
        <div class="auth-links">
            <p>이미 계정이 있으신가요? <a href="login.php">로그인</a></p>
        </div>
    </div>

    <script src="auth_scripts.js"></script>
</body>
</html>