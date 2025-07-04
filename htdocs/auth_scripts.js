document.addEventListener('DOMContentLoaded', function() {
    // ログインフォームの処理 (Login form processing)
    const loginForm = document.querySelector('.auth-form[action="login.php"]');
    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            const studentNumberInput = document.getElementById('student_number');
            const passwordInput = document.getElementById('password');
            let isValid = true;

            // エラーメッセージの表示をクリア (Clear previous error messages)
            const existingMessages = loginForm.querySelectorAll('.error-message, .success-message');
            existingMessages.forEach(msg => msg.remove());

            // 学番のバリデーション (Student number validation)
            if (!studentNumberInput.value.trim()) {
                displayMessage(loginForm, '学番を入力してください。', 'error');
                isValid = false;
            }

            // パスワードのバリデーション (Password validation)
            if (!passwordInput.value) {
                displayMessage(loginForm, 'パスワードを入力してください。', 'error');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); // フォームの送信を停止 (Stop form submission)
            }
        });
    }

    // 新規ユーザー登録フォームの処理 (New user registration form processing)
    const registerForm = document.querySelector('.auth-form[action="register_user.php"]');
    if (registerForm) {
        registerForm.addEventListener('submit', function(event) {
            const studentNumberInput = document.getElementById('student_number');
            const departmentInput = document.getElementById('department');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            let isValid = true;

            // エラーメッセージの表示をクリア (Clear previous error messages)
            const existingMessages = registerForm.querySelectorAll('.error-message, .success-message');
            existingMessages.forEach(msg => msg.remove());

            // 学番のバリデーション (Student number validation)
            if (!studentNumberInput.value.trim()) {
                displayMessage(registerForm, '学番を入力してください。', 'error');
                isValid = false;
            }

            // 学科のバリデーション (Department validation)
            if (!departmentInput.value.trim()) {
                displayMessage(registerForm, '学科を入力してください。', 'error');
                isValid = false;
            }

            // パスワードのバリデーション (Password validation)
            if (!passwordInput.value) {
                displayMessage(registerForm, 'パスワードを入力してください。', 'error');
                isValid = false;
            } else if (passwordInput.value.length < 6) {
                displayMessage(registerForm, 'パスワードは最低6文字以上である必要があります。', 'error');
                isValid = false;
            }

            // パスワード確認のバリデーション (Confirm password validation)
            if (passwordInput.value !== confirmPasswordInput.value) {
                displayMessage(registerForm, 'パスワードが一致しません。', 'error');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault(); // フォームの送信を停止 (Stop form submission)
            }
        });
    }

    /**
     * メッセージをフォームの下に表示するヘルパー関数 (Helper function to display messages below the form)
     * @param {HTMLElement} formElement - メッセージを表示するフォーム要素 (The form element to display the message under)
     * @param {string} messageText - 表示するメッセージテキスト (The message text to display)
     * @param {string} type - 'success' または 'error' (Type of message: 'success' or 'error')
     */
    function displayMessage(formElement, messageText, type) {
        const messageP = document.createElement('p');
        messageP.className = `message ${type}-message`;
        messageP.textContent = messageText;
        // フォーム内のh1タグの直後にメッセージを追加 (Add message directly after the h1 tag within the form)
        const h1 = formElement.querySelector('h1');
        if (h1) {
            h1.parentNode.insertBefore(messageP, h1.nextSibling);
        } else {
            // h1タグが見つからない場合は、フォームの最初の子要素として追加 (If h1 not found, add as first child of the form)
            formElement.prepend(messageP);
        }
    }
});