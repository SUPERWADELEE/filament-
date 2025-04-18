<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>醫療預約服務</title>
    <!-- 使用TailwindCSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- 引入Font Awesome圖標 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- LINE LIFF SDK -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    @livewireStyles
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-lg">
        <livewire:appointment />
    </div>

    @livewireScripts

    <script>
        // 顯示載入狀態
        document.addEventListener('DOMContentLoaded', function() {
            initializeLiff();
        });

        // 初始化 LIFF
        async function initializeLiff() {
            try {
                await liff.init({
                    liffId: "2007210862-ZL3R8Jy4"
                });

                if (liff.isLoggedIn()) {
                    // 獲取LINE資料
                    const profile = await liff.getProfile();
                    
                    Livewire.dispatch('lineUserProfileLoaded', [profile.userId]);
                } else {
                    // LINE未登入，無法獲取用戶資料，需要先登入LINE
                    Livewire.dispatch('lineLoginRequired');
                    liff.login();
                }
            } catch (error) {
                console.error('LIFF初始化失敗:', error);
                Livewire.dispatch('liffInitError', { 
                    errorMessage: error.message || '未知錯誤' 
                });
            }

            // 設置關閉按鈕事件
            document.addEventListener('click', function(e) {
                if (e.target && e.target.id === 'closeButton') {
                    if (liff && liff.isInClient()) {
                        liff.closeWindow();
                    }
                }
            });
        }

        // 預約成功事件監聽
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('appointmentCreated', data => {
                console.log('預約成功:', data);
            });
        });

        // 通知顯示
        window.addEventListener('notify', event => {
            const { type, message } = event.detail;
            showNotification(message, type);
        });

        // 顯示通知訊息
        function showNotification(message, type = 'info') {
            const colors = {
                'info': 'blue',
                'success': 'green',
                'warning': 'yellow',
                'error': 'red'
            };

            const color = colors[type] || 'blue';
            const icon = type === 'error' ? 'exclamation-circle' :
                type === 'warning' ? 'exclamation-triangle' :
                'info-circle';

            // 創建通知元素
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 bg-${color}-50 border-l-4 border-${color}-400 p-4 rounded shadow-lg max-w-xs z-50 transform transition-transform duration-300 ease-in-out translate-x-full`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${icon} text-${color}-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-${color}-700">${message}</p>
                    </div>
                </div>
            `;

            // 添加到文檔
            document.body.appendChild(notification);

            // 動畫顯示
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);

            // 3秒後隱藏並移除
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    </script>
</body>

</html> 