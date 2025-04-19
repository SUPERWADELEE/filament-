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

        /* 添加載入動畫的樣式 */
        #loading-screen {
            transition: opacity 0.5s ease-out;
            z-index: 9999;
        }

        #loading-screen.opacity-0 {
            opacity: 0;
        }

        #loading-screen.pointer-events-none {
            pointer-events: none;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.2);
            }
        }

        .animate-pulse {
            animation: pulse 1.5s infinite;
        }

        .animate-spin {
            animation: spin 1.5s linear infinite;
        }

        /* 增強動態背景樣式 */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: -2;
            opacity: 1;
            /* 確保背景可見 */
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #e0eaff 0%, #d2e5ff 100%);
            /* 更明顯的背景色 */
        }

        .bg-blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(40px);
            /* 減少模糊以增加可見度 */
            opacity: 0.6;
            /* 增加不透明度 */
            animation: float 20s infinite alternate ease-in-out;
            z-index: -1;
        }

        .bg-blob:nth-child(1) {
            width: 400px;
            height: 400px;
            left: -100px;
            top: -100px;
            background: rgba(74, 144, 226, 0.6);
            /* 更鮮艷的顏色 */
            animation-duration: 25s;
        }

        .bg-blob:nth-child(2) {
            width: 300px;
            height: 300px;
            right: -50px;
            top: 30%;
            background: rgba(130, 172, 255, 0.6);
            animation-duration: 30s;
            animation-delay: 2s;
        }

        .bg-blob:nth-child(3) {
            width: 350px;
            height: 350px;
            left: 20%;
            bottom: 10%;
            background: rgba(113, 128, 250, 0.5);
            animation-duration: 22s;
            animation-delay: 5s;
        }

        .bg-blob:nth-child(4) {
            width: 280px;
            height: 280px;
            right: 10%;
            bottom: -50px;
            background: rgba(102, 126, 234, 0.6);
            animation-duration: 28s;
            animation-delay: 7s;
        }

        .bg-blob:nth-child(5) {
            width: 220px;
            height: 220px;
            left: 40%;
            top: 20%;
            background: rgba(159, 122, 234, 0.5);
            animation-duration: 35s;
            animation-delay: 3s;
        }

        @keyframes float {
            0% {
                transform: translate(0, 0) rotate(0deg) scale(1);
            }

            20% {
                transform: translate(40px, 20px) rotate(45deg) scale(1.05);
            }

            40% {
                transform: translate(20px, 40px) rotate(90deg) scale(1.1);
            }

            60% {
                transform: translate(-20px, 30px) rotate(135deg) scale(1.05);
            }

            80% {
                transform: translate(-40px, 10px) rotate(180deg) scale(1);
            }

            100% {
                transform: translate(0, -30px) rotate(225deg) scale(0.95);
            }
        }

        /* 更明顯的粒子效果 */
        .particle {
            position: absolute;
            width: 8px;
            /* 更大的粒子 */
            height: 8px;
            background-color: rgba(60, 100, 255, 0.9);
            /* 更明顯的顏色 */
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
            z-index: 10;
            /* 確保粒子在最前面 */
            box-shadow: 0 0 10px rgba(60, 100, 255, 0.6);
            /* 添加發光效果 */
        }

        /* 使粒子動畫更明顯 */
        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }

            5% {
                opacity: 1;
            }

            95% {
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) translateX(50px);
                opacity: 0;
            }
        }

        /* 修改主容器的透明度，讓背景更明顯 */
        .container {
            position: relative;
            z-index: 1;
            background: rgba(255, 255, 255, 0.5);
            /* 減少透明度 */
            backdrop-filter: blur(5px);
            /* 減少模糊度 */
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
        }

        /* 添加到樣式中 - 解決移動設備上的問題 */
        @supports (-webkit-touch-callout: none) {

            .animated-bg,
            .bg-blob,
            .particle {
                transform: translateZ(0);
                /* 強制硬件加速 */
            }

            .container {
                backdrop-filter: none;
                /* 某些移動設備不支持 backdrop-filter */
                background: rgba(255, 255, 255, 0.9);
                /* 改用更高不透明度 */
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <!-- 動態背景 -->
    <div class="animated-bg">
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
        <div class="bg-blob"></div>
    </div>

    <!-- 載入動畫 -->
    <div id="loading-screen" class="fixed inset-0 flex items-center justify-center bg-gradient-to-br from-blue-900 to-indigo-900">
        <div class="text-center max-w-md px-4">
            <div class="relative w-24 h-24 mx-auto mb-8">
                <div class="absolute top-0 left-0 w-full h-full rounded-full border-8 border-blue-200 opacity-30"></div>
                <div class="absolute top-0 left-0 w-full h-full rounded-full border-t-8 border-blue-500 animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <i class="fas fa-heartbeat text-blue-500 text-2xl animate-pulse"></i>
                </div>
            </div>
            <h2 class="text-xl font-bold text-white mb-2">載入中...</h2>
            <p class="text-blue-200 mb-6">正在準備您的預約記錄</p>

            <!-- 健康小貼士輪播 -->
            <div class="bg-white/10 rounded-lg p-4 backdrop-blur-sm">
                <h3 class="text-white text-sm mb-2"><i class="fas fa-lightbulb mr-2 text-yellow-300"></i>健康小貼士</h3>
                <div id="health-tips" class="text-blue-100 text-sm italic">
                    <!-- 小貼士會通過 JavaScript 動態更新 -->
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 max-w-lg backdrop-blur-md bg-white/60 rounded-2xl shadow-lg">
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
        // 健康小貼士數據
        const healthTips = [
            "每天至少喝八杯水，保持身體水分充足。",
            "保持規律作息，每晚睡眠 7-8 小時。",
            "每天進行 30 分鐘中等強度的運動。",
            "多吃蔬果，減少加工食品的攝取。",
            "定期健康檢查是預防疾病的最佳方式。",
            "保持良好的姿勢，避免長時間低頭看手機。",
            "洗手是預防傳染病的簡單有效方法。",
            "壓力管理對身心健康至關重要。"
        ];

        function showRandomTip() {
            const tipElement = document.getElementById('health-tips');
            const randomTip = healthTips[Math.floor(Math.random() * healthTips.length)];
            tipElement.textContent = randomTip;
        }

        // 初始顯示一個小貼士
        showRandomTip();

        // 每 5 秒更換一次小貼士
        setInterval(showRandomTip, 5000);

        // 將粒子創建代碼移出 DOMContentLoaded 事件，單獨執行
        function initParticles() {
            const animatedBg = document.querySelector('.animated-bg');
            if (!animatedBg) return;

            // 創建50個浮動粒子
            for (let i = 0; i < 50; i++) {
                createParticle(animatedBg);
            }

            // 每隔0.5秒創建3個新粒子
            setInterval(() => {
                for (let i = 0; i < 3; i++) {
                    createParticle(animatedBg);
                }
            }, 500);
        }

        // 改進粒子創建函數，創建更明顯的粒子
        function createParticle(container) {
            const particle = document.createElement('div');
            particle.className = 'particle';

            // 隨機位置、大小和動畫時間
            const size = 5 + Math.random() * 8; // 更大的粒子
            const left = Math.random() * 100;
            const animationDuration = 8 + Math.random() * 10;
            const delay = Math.random() * 2;

            // 更鮮艷的顏色
            const colors = [
                'rgba(60, 120, 255, 0.9)',
                'rgba(100, 80, 255, 0.9)',
                'rgba(120, 100, 255, 0.9)',
                'rgba(80, 160, 255, 0.9)'
            ];
            const color = colors[Math.floor(Math.random() * colors.length)];

            // 設置粒子樣式
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${left}%`;
            particle.style.bottom = '0px';
            particle.style.backgroundColor = color;
            particle.style.opacity = 0.7 + Math.random() * 0.3; // 更高的不透明度
            particle.style.animationDuration = `${animationDuration}s`;
            particle.style.animationDelay = `${delay}s`;
            particle.style.boxShadow = `0 0 ${size/2}px ${color}`; // 添加發光效果

            // 添加到容器
            container.appendChild(particle);

            // 動畫結束後移除粒子
            setTimeout(() => {
                particle.remove();
            }, (animationDuration + delay) * 1000);
        }

        // DOMContentLoaded 事件中調用初始化
        document.addEventListener('DOMContentLoaded', function() {
            // 設置關閉按鈕事件
            document.getElementById('closeButton')?.addEventListener('click', function() {
                if (window.liff) {
                    liff.closeWindow();
                }
            });

            // 初始化粒子效果
            setTimeout(initParticles, 500); // 延遲500毫秒執行，確保DOM已完全載入
        });

        document.addEventListener('livewire:initialized', () => {
            initializeLiff();
            setupNotificationSystem();
        });

        // 初始化 LINE LIFF SDK
        function initializeLiff() {
            const loadingScreen = document.getElementById('loading-screen');

            // SDK 未載入的情況
            if (typeof liff === 'undefined') {
                loadingScreen.style.display = 'none';
                return;
            }

            // 初始化 LIFF
            liff.init({
                liffId: "2007210862-GWWM0wVK"
            }).then(() => {
                handleLiffInitialized();

                // LIFF 初始化成功，漸進隱藏載入畫面
                setTimeout(() => {
                    loadingScreen.classList.add('opacity-0', 'pointer-events-none');
                    setTimeout(() => {
                        loadingScreen.style.display = 'none';
                    }, 500);
                }, 1500); // 延遲 1.5 秒以展示動畫

            }).catch(err => {
                loadingScreen.style.display = 'none';
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