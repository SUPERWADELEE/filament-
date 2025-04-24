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

    <style>
        /* 載入動畫樣式 */
        #loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #loading-overlay.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .medical-loader {
            position: relative;
            width: 200px;
            height: 200px;
        }

        .medical-icon {
            position: absolute;
            color: white;
            opacity: 0;
            transform: scale(0.5);
            animation: fadeInOut 6s infinite;
        }

        .medical-icon:nth-child(1) {
            top: 40%;
            left: 30%;
            animation-delay: 0s;
        }

        .medical-icon:nth-child(2) {
            top: 20%;
            left: 50%;
            animation-delay: 1s;
        }

        .medical-icon:nth-child(3) {
            top: 60%;
            left: 60%;
            animation-delay: 2s;
        }

        .medical-icon:nth-child(4) {
            top: 30%;
            left: 70%;
            animation-delay: 3s;
        }

        .medical-icon:nth-child(5) {
            top: 70%;
            left: 40%;
            animation-delay: 4s;
        }

        @keyframes fadeInOut {

            0%,
            100% {
                opacity: 0;
                transform: scale(0.5);
            }

            20%,
            80% {
                opacity: 1;
                transform: scale(1);
            }
        }

        .loading-text {
            position: absolute;
            bottom: -40px;
            left: 0;
            right: 0;
            text-align: center;
            color: white;
            font-size: 1.2rem;
            letter-spacing: 0.1em;
        }

        .loading-text:after {
            content: '...';
            display: inline-block;
            width: 20px;
            text-align: left;
            animation: dots 1.5s infinite;
        }

        @keyframes dots {

            0%,
            20% {
                content: '.';
            }

            40% {
                content: '..';
            }

            60%,
            100% {
                content: '...';
            }
        }

        .doctor-tip {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.15);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
            color: white;
            max-width: 80%;
            margin: 0 auto;
            transform: translateY(20px);
            opacity: 0;
            animation: tipFadeIn 0.8s forwards 1s;
        }

        @keyframes tipFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .doctor-avatar {
            position: absolute;
            bottom: 120px;
            left: 40px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #667eea;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* 預約成功動畫樣式 */
        #success-animation {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #48bb78 0%, #38b2ac 100%);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: opacity 0.8s ease, visibility 0.8s ease;
            opacity: 0;
            visibility: hidden;
        }

        #success-animation.show {
            opacity: 1;
            visibility: visible;
        }

        .success-container {
            text-align: center;
            color: white;
            max-width: 90%;
        }

        .success-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 30px;
            position: relative;
            overflow: hidden;
        }

        .success-icon::before {
            content: '';
            position: absolute;
            top: -10%;
            left: -10%;
            right: -10%;
            bottom: -10%;
            background: rgba(72, 187, 120, 0.1);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .checkmark {
            transform: scale(0);
            opacity: 0;
            animation: scaleIn 0.5s forwards 0.2s;
        }

        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background: white;
            opacity: 0.8;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 0.8;
            }

            70% {
                transform: scale(1.5);
                opacity: 0;
            }

            100% {
                transform: scale(1);
                opacity: 0;
            }
        }

        @keyframes flyUpDown {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
            }

            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        .success-text {
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.6s forwards 0.5s;
        }

        .success-details {
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.6s forwards 0.8s;
        }

        .success-button {
            transform: translateY(20px);
            opacity: 0;
            animation: fadeUp 0.6s forwards 1.1s;
        }

        @keyframes fadeUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* 新增的動態背景樣式 - 與歷史頁面不同 */
        .dynamic-bg {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
            z-index: 0;
            background: linear-gradient(135deg, #f0f4ff 0%, #e6f7ff 100%);
            pointer-events: none;
        }

        /* 波浪動畫 */
        .wave {
            position: absolute;
            background: rgba(100, 181, 246, 0.5);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            width: 200vw;
            height: 200vw;
            animation: wave 15s linear infinite;
            will-change: transform, opacity;
        }

        .wave:nth-child(1) {
            left: 30%;
            top: 40%;
            animation-delay: 0s;
        }

        .wave:nth-child(2) {
            left: 60%;
            top: 60%;
            animation-delay: 4s;
        }

        .wave:nth-child(3) {
            left: 40%;
            top: 70%;
            animation-delay: 8s;
        }

        .wave:nth-child(4) {
            left: 70%;
            top: 30%;
            animation-delay: 2s;
            width: 180vw;
            height: 180vw;
            background: rgba(125, 150, 240, 0.3);
        }

        .wave:nth-child(5) {
            left: 20%;
            top: 20%;
            animation-delay: 6s;
            width: 150vw;
            height: 150vw;
            background: rgba(90, 120, 220, 0.35);
        }

        @keyframes wave {
            0% {
                transform: translate(-50%, -50%) scale(0);
                opacity: 0.8;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 0;
            }
        }

        /* 漂浮的醫療圖標 */
        .float-icon {
            position: absolute;
            font-size: 2rem;
            opacity: 0.5;
            color: #3b82f6;
            animation: float 20s linear infinite;
            z-index: 1;
            will-change: transform;
            pointer-events: none;
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
            /* 添加發光效果 */
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg) scale(1);
            }

            50% {
                transform: translateY(40vh) rotate(180deg) scale(1.2);
            }

            100% {
                transform: translateY(-20vh) rotate(360deg) scale(1);
            }
        }

        /* 主容器樣式 */
        .container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.6);
            backdrop-filter: blur(5px);
            -webkit-backdrop-filter: blur(5px);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 2rem auto;
        }

        /* 解決移動設備上的問題 */
        @supports (-webkit-touch-callout: none) {

            .dynamic-bg,
            .wave,
            .float-icon {
                transform: translateZ(0);
                backface-visibility: hidden;
                -webkit-backface-visibility: hidden;
                -webkit-perspective: 1000;
                perspective: 1000;
            }

            .container {
                backdrop-filter: none;
                -webkit-backdrop-filter: none;
                background: rgba(255, 255, 255, 0.8);
            }
        }

        /* 特別針對小螢幕設備的樣式調整 */
        @media (max-width: 640px) {
            .wave {
                width: 300vw;
                height: 300vw;
            }

            .float-icon {
                opacity: 0.3;
                font-size: 1.5rem;
            }
        }

        /* 添加body樣式 */
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            background: transparent;
            /* 確保body背景是透明的 */
            -webkit-tap-highlight-color: transparent;
            /* 移除移動設備上的點擊高亮 */
        }

        /* 添加類似歷史頁面的動態背景層 */
        .animated-bg-layer {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 2;
            pointer-events: none;
        }

        .bg-particle {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: rgba(60, 100, 255, 0.8);
            border-radius: 50%;
            animation: particleFloat 12s infinite linear;
            z-index: 3;
            box-shadow: 0 0 10px rgba(60, 100, 255, 0.6);
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }

            5% {
                opacity: 0.8;
            }

            90% {
                opacity: 0.8;
            }

            100% {
                transform: translateY(-50vh) translateX(50px);
                opacity: 0;
            }
        }
    </style>
</head>

<body>
    <!-- 動態背景 -->
    <div class="dynamic-bg">
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
        <div class="wave"></div>
    </div>

    <!-- 粒子背景層 -->
    <div class="animated-bg-layer" id="particles-container"></div>

    <!-- 載入動畫覆蓋層 -->
    <div id="loading-overlay">
        <div class="relative">
            <div class="medical-loader">
                <i class="medical-icon fas fa-stethoscope text-4xl"></i>
                <i class="medical-icon fas fa-heartbeat text-4xl"></i>
                <i class="medical-icon fas fa-user-md text-4xl"></i>
                <i class="medical-icon fas fa-capsules text-4xl"></i>
                <i class="medical-icon fas fa-hospital text-4xl"></i>
                <div class="loading-text">準備您的預約系統</div>
            </div>

            <div class="doctor-avatar">
                <i class="fas fa-user-md"></i>
            </div>

            <div class="doctor-tip">
                <p class="text-sm font-medium"><i class="fas fa-lightbulb mr-2"></i>醫生小提示</p>
                <p class="text-xs mt-2" id="doctor-advice">定期預約健康檢查是預防疾病的最佳方式</p>
            </div>
        </div>
    </div>
    <!-- 預約成功動畫 -->
    <div id="success-animation">
        <div class="success-container">
            <div class="success-icon">
                <i class="fas fa-check text-green-500 text-5xl checkmark"></i>
            </div>
            <h1 class="text-2xl font-bold mb-3 success-text">預約成功！</h1>
            <div class="success-details mb-8">
                <p class="text-white opacity-90 mb-1">您的預約已確認</p>
                <p class="text-white opacity-90" id="success-appointment-details">
                    <!-- 將由JavaScript填充 -->
                </p>
            </div>
            <button id="success-done-button" class="bg-white text-green-600 font-semibold py-3 px-8 rounded-full shadow-lg hover:bg-gray-100 transition-colors success-button">
                完成
            </button>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12 max-w-lg">
        <livewire:appointment />
    </div>

    @livewireScripts

    <script>
        // 醫生小提示陣列
        const doctorAdvices = [
            "定期預約健康檢查是預防疾病的最佳方式",
            "及時就醫，遠離疾病困擾",
            "線上預約，節省您的等待時間",
            "如有不適，請不要擔心，我們會提供專業幫助",
            "選擇合適的專科醫生，能更有效地解決健康問題",
            "準時赴約，有助於醫生更好地安排診療時間",
            "帶齊您的病歷資料，有助於醫生做出更準確的診斷",
            "預約前整理好您的症狀描述，能幫助診療更高效"
        ];

        // 隨機顯示醫生小提示
        function showRandomAdvice() {
            const adviceElement = document.getElementById('doctor-advice');
            const randomAdvice = doctorAdvices[Math.floor(Math.random() * doctorAdvices.length)];
            adviceElement.textContent = randomAdvice;
        }

        // 每4秒更換一次小提示
        setInterval(showRandomAdvice, 4000);

        // 初始顯示一條小提示
        showRandomAdvice();

        // 創建漂浮醫療圖標
        function createFloatingIcons() {
            const icons = [
                'fa-heartbeat',
                'fa-stethoscope',
                'fa-user-md',
                'fa-hospital',
                'fa-pills',
                'fa-syringe',
                'fa-notes-medical',
                'fa-briefcase-medical'
            ];

            const bg = document.querySelector('.dynamic-bg');
            if (!bg) {
                return;
            }

            // 根據螢幕大小決定創建多少個圖標
            const iconCount = window.innerWidth < 640 ? 20 : 40;

            // 清除現有圖標
            const existingIcons = document.querySelectorAll('.float-icon');
            existingIcons.forEach(icon => icon.remove());

            // 創建圖標 - 分布在整個屏幕
            for (let i = 0; i < iconCount; i++) {
                const icon = document.createElement('i');
                const randomIcon = icons[Math.floor(Math.random() * icons.length)];
                icon.className = `float-icon fas ${randomIcon}`;

                // 讓圖標初始位置分布在整個頁面高度
                const left = Math.random() * 100; // 橫向隨機分布
                const bottom = Math.random() * 120 - 20; // 從頁面底部下方到整個頁面高度
                const size = window.innerWidth < 640 ?
                    (1.4 + Math.random() * 0.8) : // 手機版更大的圖標
                    (1.2 + Math.random() * 1.4); // 桌面版圖標大小

                // 更短的動畫時間
                const duration = window.innerWidth < 640 ?
                    (8 + Math.random() * 12) : // 手機版更短動畫時間
                    (12 + Math.random() * 20); // 桌面版動畫時間

                const delay = Math.random() * 5; // 更短的延遲

                // 設置樣式 - 增加不透明度
                icon.style.left = `${left}%`;
                icon.style.bottom = `${bottom}%`;
                icon.style.fontSize = `${size}rem`;
                icon.style.animationDuration = `${duration}s`;
                icon.style.animationDelay = `${delay}s`;
                icon.style.opacity = (0.5 + Math.random() * 0.3).toString(); // 更高的不透明度

                bg.appendChild(icon);
            }
        }

        // 重新調整動畫元素大小的函數
        function resizeBackgroundElements() {
            // 在視窗大小改變時調整背景元素
            const isMobile = window.innerWidth < 640;
            const waves = document.querySelectorAll('.wave');
            const icons = document.querySelectorAll('.float-icon');

            // 調整波浪大小
            waves.forEach((wave, index) => {
                if (index >= 3) return; // 只調整前三個波浪，後面的有固定大小

                wave.style.width = isMobile ? '300vw' : '200vw';
                wave.style.height = isMobile ? '300vw' : '200vw';
            });

            // 調整圖標大小和不透明度 - 移動版增加可見度
            if (isMobile) {
                icons.forEach(icon => {
                    // 提高移動版的不透明度和大小
                    const currentSize = parseFloat(icon.style.fontSize);
                    if (currentSize < 1.5) {
                        icon.style.fontSize = (currentSize * 1.2) + 'rem';
                    }

                    const currentOpacity = parseFloat(icon.style.opacity || 0.4);
                    icon.style.opacity = Math.min(currentOpacity * 1.2, 0.6).toString();
                });
            }

            // 如果是移動設備，重新創建圖標以適應屏幕大小
            if (window.innerWidth !== window._lastWidth) {
                window._lastWidth = window.innerWidth;
                setTimeout(createFloatingIcons, 500);
            }
        }

        // 顯示載入狀態
        document.addEventListener('DOMContentLoaded', function() {
            // 創建各種動畫元素
            createFloatingIcons();
            createParticles();

            // 設置窗口大小變化監聽
            window.addEventListener('resize', function() {
                resizeBackgroundElements();
                createParticles();
            });

            // 初始調用一次
            resizeBackgroundElements();

            // 初始化LIFF
            initializeLiff();
        });

        // 初始化 LIFF
        async function initializeLiff() {
            const loadingOverlay = document.getElementById('loading-overlay');

            try {
                await liff.init({
                    liffId: "2007210862-ZL3R8Jy4"
                });

                // 延遲隱藏載入動畫，讓用戶有時間看到精美的動畫
                setTimeout(() => {
                    loadingOverlay.classList.add('hidden');
                }, 3000);

                if (liff.isLoggedIn()) {
                    // 獲取LINE資料
                    const profile = await liff.getProfile();

                    Livewire.dispatch('lineUserProfileLoaded', [profile.userId, profile.displayName]);
                } else {
                    // LINE未登入，無法獲取用戶資料，需要先登入LINE
                    Livewire.dispatch('lineLoginRequired');
                    liff.login();
                }
            } catch (error) {
                // 出錯時也要隱藏載入動畫
                loadingOverlay.classList.add('hidden');

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
                // 顯示成功動畫
                const successAnimation = document.getElementById('success-animation');
                const successDetails = document.getElementById('success-appointment-details');

                // 如果有預約數據，顯示預約詳情
                if (data && data.doctor && data.date && data.time) {
                    successDetails.innerHTML = `
                        醫生：${data.doctor}<br>
                        日期：${data.date}<br>
                        時間：${data.time}
                    `;
                }

                // 創建動畫彩色碎片
                createConfetti();

                // 顯示成功動畫
                successAnimation.classList.add('show');

                // 設置成功按鈕監聽
                document.getElementById('success-done-button').addEventListener('click', function() {
                    successAnimation.classList.remove('show');

                    // 如果是在LINE應用內，關閉窗口或重定向到歷史頁面
                    if (liff && liff.isInClient()) {
                        // 可以選擇關閉窗口
                        // liff.closeWindow();

                        // 或重定向到預約歷史頁面
                        setTimeout(() => {
                            window.location.href = "{{ route('line.appointment.history') }}";
                        }, 500);
                    } else {
                        // 在瀏覽器中，重定向到預約歷史頁面
                        setTimeout(() => {
                            window.location.href = "{{ route('line.appointment.history') }}";
                        }, 500);
                    }
                });
            });
        });

        // 通知顯示
        window.addEventListener('notify', event => {
            const {
                type,
                message
            } = event.detail;
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
        // 創建彩色碎片動畫
        function createConfetti() {
            const successAnimation = document.getElementById('success-animation');
            const colors = ['#48bb78', '#38b2ac', '#4299e1', '#9f7aea', '#ed8936', '#ecc94b'];
            const shapes = ['circle', 'square', 'triangle'];

            // 生成50個碎片
            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';

                // 隨機位置、顏色和形狀
                const left = Math.random() * 100;
                const color = colors[Math.floor(Math.random() * colors.length)];
                const shape = shapes[Math.floor(Math.random() * shapes.length)];
                const animationDuration = 3 + Math.random() * 3;
                const size = 5 + Math.random() * 10;

                confetti.style.left = `${left}%`;
                confetti.style.top = '60%';
                confetti.style.background = color;
                confetti.style.width = `${size}px`;
                confetti.style.height = `${size}px`;
                confetti.style.animationDuration = `${animationDuration}s`;

                if (shape === 'circle') {
                    confetti.style.borderRadius = '50%';
                } else if (shape === 'triangle') {
                    confetti.style.width = '0';
                    confetti.style.height = '0';
                    confetti.style.background = 'transparent';
                    confetti.style.borderLeft = `${size/2}px solid transparent`;
                    confetti.style.borderRight = `${size/2}px solid transparent`;
                    confetti.style.borderBottom = `${size}px solid ${color}`;
                }

                // 添加動畫
                confetti.style.animation = `flyUpDown ${animationDuration}s forwards`;
                confetti.style.animationTimingFunction = 'ease-out';
                confetti.style.animationDelay = `${Math.random() * 0.5}s`;
                confetti.style.position = 'absolute';
                confetti.style.zIndex = '-1';

                successAnimation.appendChild(confetti);

                // 動畫結束後移除元素
                setTimeout(() => {
                    confetti.remove();
                }, animationDuration * 1000);
            }
        }

        // 創建粒子效果
        function createParticles() {
            const container = document.getElementById('particles-container');
            if (!container) {
                return;
            }

            // 清除現有粒子
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }

            // 創建新粒子
            const particleCount = window.innerWidth < 640 ? 30 : 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'bg-particle';

                // 設置隨機屬性
                const size = 5 + Math.random() * 8;
                const left = Math.random() * 100;
                const animationDuration = 8 + Math.random() * 10;
                const delay = Math.random() * 5;

                // 隨機顏色
                const hue = 210 + Math.random() * 30; // 藍色色調範圍
                const color = `hsla(${hue}, 80%, 60%, 0.8)`;

                // 設置樣式
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.bottom = '0';
                particle.style.backgroundColor = color;
                particle.style.boxShadow = `0 0 ${size/2}px ${color}`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${delay}s`;

                container.appendChild(particle);
            }
        }
    </script>
</body>

</html>