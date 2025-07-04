document.addEventListener('DOMContentLoaded', function() {
    const totalCreditsElement = document.getElementById('total-credits');
    const categoryCreditsContainer = document.getElementById('category-credits-list');
    const messageContainer = document.getElementById('credits-status-message');

    let currentUserId = null; 

    // PHP에서 넘어온 사용자 ID를 확인합니다.
    // credits_status.php 파일에서 currentUserIdFromPHP 변수를 설정해 주어야 합니다.
    // 예: <script>const currentUserIdFromPHP = <?php echo json_encode($_SESSION['user_id'] ?? null); ?>;</script>
    if (typeof window.currentUserIdFromPHP !== 'undefined' && window.currentUserIdFromPHP !== null) {
        currentUserId = window.currentUserIdFromPHP;
    } else {
        // 로그인되지 않은 경우 메시지를 표시하고 종료합니다.
        if (messageContainer) {
            messageContainer.innerHTML = '<p class="error-message">ログインしていません。ログイン後に単位取得状況を確認できます。</p>';
        }
        if (totalCreditsElement) totalCreditsElement.textContent = '0';
        console.warn("警告: currentUserIdFromPHPが定義されていないかnullです。単位取得状況をロードできません。");
        return; 
    }

    /**
     * 서버로부터 사용자의 학점 현황을 불러와 표시합니다.
     */
    function fetchCreditsStatus() {
        if (currentUserId === null) {
            console.error("ユーザーIDが設定されていません。単位取得状況をロードできません。");
            return;
        }

        // 학점 현황을 가져올 PHP 스크립트 (예: get_credits_status.php)에 요청을 보냅니다.
        fetch(`get_credits_status.php?user_id=${currentUserId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // 총 취득 학점 표시
                    if (totalCreditsElement) {
                        totalCreditsElement.textContent = data.total_credits;
                    }

                    // 카테고리별 학점 표시
                    if (categoryCreditsContainer) {
                        categoryCreditsContainer.innerHTML = ''; // 기존 목록 초기화
                        if (Object.keys(data.category_credits).length > 0) {
                            for (const category in data.category_credits) {
                                const listItem = document.createElement('li');
                                listItem.textContent = `${category}: ${data.category_credits[category]}単位`;
                                categoryCreditsContainer.appendChild(listItem);
                            }
                        } else {
                            categoryCreditsContainer.innerHTML = '<li>まだ取得した単位がありません。</li>';
                        }
                    }
                    if (messageContainer) {
                        messageContainer.innerHTML = '<p class="success-message">単位取得状況が正常に更新されました。</p>';
                    }
                    console.log('単位取得状況が正常にロードされました。', data);

                } else {
                    // 서버에서 오류 응답을 보낸 경우
                    if (messageContainer) {
                        messageContainer.innerHTML = `<p class="error-message">単位取得状況の取得に失敗しました: ${data.message}</p>`;
                    }
                    console.error('単位取得状況の取得に失敗しました:', data.message);
                }
            })
            .catch(error => {
                // 네트워크 또는 파싱 오류 발생 시
                if (messageContainer) {
                    messageContainer.innerHTML = '<p class="error-message">単位取得状況の読み込み中にエラーが発生しました。ネットワーク接続を確認してください。</p>';
                }
                console.error('単位取得状況のロード中にエラーが発生しました:', error);
            });
    }

    // 페이지 로드 시 학점 현황 불러오기
    fetchCreditsStatus();
});