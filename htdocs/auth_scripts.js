document.addEventListener('DOMContentLoaded', function() {
    // ログインフォームのバリデーション
    const loginForm = document.querySelector('.auth-form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            // HTMLのinput要素のname属性が "student_number" に対応するように変更
            const studentNumberInput = loginForm.querySelector('input[name="student_number"]'); 
            const passwordInput = loginForm.querySelector('input[name="password"]');

            // 簡単なバリデーション (空値チェック)
            if (studentNumberInput.value.trim() === '') { 
                alert('学番を入力してください。'); 
                studentNumberInput.focus(); 
                event.preventDefault(); // フォームの送信を阻止
                return false;
            }

            if (passwordInput.value.trim() === '') {
                alert('パスワードを入力してください。');
                passwordInput.focus();
                event.preventDefault(); // フォームの送信を阻止
                return false;
            }
            // 必要に応じて、さらに高度なバリデーションロジックをここに追加できます。
        });
    }

    // 新規ユーザー登録フォームのバリデーション (register_user.phpで使用)
    // この部分は register_user.php で必要に応じて使用します。
    const registerForm = document.querySelector('.auth-form[action="register_user.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const studentNumberInput = registerForm.querySelector('input[name="student_number"]'); // 学番
            const emailInput = registerForm.querySelector('input[name="email"]');
            const passwordInput = registerForm.querySelector('input[name="password"]');
            const confirmPasswordInput = registerForm.querySelector('input[name="confirm_password"]');

            if (studentNumberInput.value.trim() === '') {
                alert('学番を入力してください。');
                studentNumberInput.focus();
                event.preventDefault();
                return false;
            }

            if (emailInput.value.trim() === '' || !emailInput.value.includes('@')) {
                alert('有効なメールアドレスを入力してください。');
                emailInput.focus();
                event.preventDefault();
                return false;
            }

            if (passwordInput.value.trim() === '') {
                alert('パスワードを入力してください。');
                passwordInput.focus();
                event.preventDefault();
                return false;
            }

            if (passwordInput.value !== confirmPasswordInput.value) {
                alert('パスワードが一致しません。');
                confirmPasswordInput.focus();
                event.preventDefault();
                return false;
            }
            // 必要に応じて、さらに高度なバリデーションロジックをここに追加できます。
        });
    }
});