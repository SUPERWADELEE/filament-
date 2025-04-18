<!-- resources/views/line/appointment-history-livewire.blade.php -->
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>診療預約歷史</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
    @livewireStyles
    <style>
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
            text-align: center;
        }

        .badge-blue {
            background-color: rgba(59, 130, 246, 0.1);
            color: rgb(37, 99, 235);
        }

        .badge-green {
            background-color: rgba(16, 185, 129, 0.1);
            color: rgb(5, 150, 105);
        }

        .badge-yellow {
            background-color: rgba(245, 158, 11, 0.1);
            color: rgb(217, 119, 6);
        }

        .badge-red {
            background-color: rgba(239, 68, 68, 0.1);
            color: rgb(220, 38, 38);
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-lg">
        <div class="text-center mb-10">
            <div class="inline-block p-3 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-history text-blue-500 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">預約歷史記錄</h1>
            <p class="text-gray-600">查看您的診療預約歷史</p>
        </div>

        @livewire('appointment-history', ['lineUserId' => $lineUserId ?? null])

        <div class="mt-8 flex justify-between">
            <a href="{{ route('line.appointment') }}" class="block w-full mr-2 bg-blue-500 text-white font-medium py-3 px-4 rounded-lg hover:bg-blue-600 text-center">
                <i class="fas fa-calendar-plus mr-2"></i>新增預約
            </a>
            <button id="closeButton" class="block w-full ml-2 bg-gray-500 text-white font-medium py-3 px-4 rounded-lg hover:bg-gray-600">
                返回LINE
            </button>
        </div>

        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>若有任何問題，請聯繫我們的客服</p>
            <p class="mt-1">&copy; 2025 醫療預約系統</p>
        </div>
    </div>

    @livewireScripts
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 設置關閉按鈕事件
            document.getElementById('closeButton').addEventListener('click', function() {
                if (window.liff) {
                    liff.closeWindow();
                }
            });
        });

        document.addEventListener('livewire:initialized', () => {
            initializeLiff();
            setupNotificationSystem();
        });

        // 初始化 LINE LIFF SDK
        function initializeLiff() {
            // SDK 未載入的情況
            if (typeof liff === 'undefined') {
                console.log('liff未載入');
                return;
            }

            // 初始化 LIFF
            liff.init({
                liffId: "2007210862-GWWM0wVK"
            }).then(() => {
                handleLiffInitialized();
            }).catch(err => {
                console.log('err', err);
            });
        }

        // 處理 LIFF 初始化成功
        function handleLiffInitialized() {
            if (liff.isLoggedIn()) {
                fetchUserProfile();
            } else {
                liff.login();
            }
        }

        // 獲取用戶資料
        function fetchUserProfile() {
            liff.getProfile()
                .then(profile => {
                    Livewire.dispatch('lineUserProfileLoaded', [profile.userId]);
                })
                .catch(err => {
                    console.log('err', err);

                    // 如果是權限問題，嘗試重新登入
                    if (err.message.includes("permission") || err.message.includes("scope")) {
                        setTimeout(() => liff.login(), 1500);
                    }
                });
        }

        // 設置通知系統
        function setupNotificationSystem() {
            Livewire.on('showNotification', (data) => {
                showNotification(data.message, data.type);
            });
        }

        // 顯示通知
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

            // 動畫顯示與隱藏
            setTimeout(() => notification.classList.remove('translate-x-full'), 10);
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>

</html>