<!-- resources/views/line/appointmentHistory.blade.php -->
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title>診療預約歷史</title>
    <!-- 使用TailwindCSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <!-- 引入Font Awesome圖標 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- LINE LIFF SDK -->
    <script charset="utf-8" src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>
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

        <div id="loginMessage" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-6 hidden">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        請先登入LINE以查看您的預約歷史
                    </p>
                </div>
            </div>
        </div>

        <div id="loadingState" class="text-center py-10">
            <div class="inline-block loading-spinner h-12 w-12 border-4 border-blue-200 border-t-blue-500 rounded-full"></div>
            <p class="mt-4 text-gray-600">載入中，請稍候...</p>
        </div>

        <!-- 預約歷史列表 -->
        <div id="historyContainer" class="mb-6 hidden">
            <!-- 切換标签 -->
            <div class="flex mb-4">
                <button id="upcomingTab" class="flex-1 py-2 px-4 text-center bg-blue-500 text-white rounded-tl-lg font-medium">
                    即將到來
                </button>
                <button id="pastTab" class="flex-1 py-2 px-4 text-center bg-gray-200 text-gray-700 rounded-tr-lg font-medium">
                    歷史記錄
                </button>
            </div>
            
            <!-- 無預約提示 -->
            <div id="noAppointments" class="hidden bg-white rounded-lg shadow-md p-6 text-center">
                <div class="inline-block p-3 bg-blue-50 rounded-full mb-4">
                    <i class="fas fa-calendar-times text-blue-400 text-2xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-800 mb-2">沒有預約記錄</h3>
                <p class="text-gray-600">您目前沒有任何預約記錄</p>
                <a href="{{ route('line.appointment') }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                    立即預約
                </a>
            </div>
            
            <!-- 預約列表 -->
            <div id="appointmentsList">
                <!-- 預約卡片將在這裡動態生成 -->
            </div>
        </div>

        <!-- 權限問題的手動輸入表單 -->
        <div id="manualInputForm" class="bg-white rounded-xl shadow-xl overflow-hidden hidden mb-6">
            <div class="h-2 bg-gradient-to-r from-yellow-400 to-yellow-500"></div>
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">權限不足</h3>
                <p class="text-gray-600 mb-4">由於權限設定問題，無法自動獲取您的資料。請手動輸入您的LINE用戶ID：</p>
                
                <div class="mb-4">
                    <label for="manualLineId" class="block text-gray-700 text-sm font-medium mb-2">LINE用戶ID</label>
                    <input type="text" id="manualLineId" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="請輸入您的LINE用戶ID">
                </div>
                
                <button id="submitManualId" class="w-full bg-blue-500 text-white font-medium py-2 px-4 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    提交
                </button>
            </div>
        </div>

        <!-- 底部按鈕 -->
        <div class="mt-8 flex justify-between">
            <a href="{{ route('line.appointment') }}" class="block w-full mr-2 bg-blue-500 text-white font-medium py-3 px-4 rounded-lg hover:bg-blue-600 text-center">
                <i class="fas fa-calendar-plus mr-2"></i>新增預約
            </a>
            <button id="closeButton" class="block w-full ml-2 bg-gray-500 text-white font-medium py-3 px-4 rounded-lg hover:bg-gray-600">
                返回LINE
            </button>
        </div>

        <!-- 底部資訊 -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>若有任何問題，請聯繫我們的客服</p>
            <p class="mt-1">&copy; 2025 醫療預約系統</p>
        </div>
    </div>

    <script>
        // 初始化變數
        let userLineId = null;
        let appointments = [];
        
        // 顯示載入狀態
        document.getElementById('loadingState').classList.remove('hidden');
        
        // 初始化 LIFF
        document.addEventListener('DOMContentLoaded', async function() {
            await initializeLiff();
            
            // 設置關閉按鈕事件
            document.getElementById('closeButton').addEventListener('click', function() {
                liff.closeWindow();
            });
            
            // 初始化標籤切換事件
            document.getElementById('upcomingTab').addEventListener('click', function() {
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('bg-blue-500', 'text-white');
                document.getElementById('pastTab').classList.remove('bg-blue-500', 'text-white');
                document.getElementById('pastTab').classList.add('bg-gray-200', 'text-gray-700');
                filterAppointments('upcoming');
            });
            
            document.getElementById('pastTab').addEventListener('click', function() {
                this.classList.remove('bg-gray-200', 'text-gray-700');
                this.classList.add('bg-blue-500', 'text-white');
                document.getElementById('upcomingTab').classList.remove('bg-blue-500', 'text-white');
                document.getElementById('upcomingTab').classList.add('bg-gray-200', 'text-gray-700');
                filterAppointments('past');
            });
        });

        // 初始化 LIFF
        async function initializeLiff() {
 
            if (typeof liff === 'undefined') {
                document.getElementById('loadingState').classList.add('hidden');
                
                // 顯示錯誤訊息
                const errorContainer = document.createElement('div');
                errorContainer.className = 'bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6';
                errorContainer.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-bold">LINE SDK未載入</p>
                            <p class="text-sm text-red-700">請確認您的網路連接並重新整理頁面</p>
                        </div>
                    </div>
                `;
                document.querySelector('.container').appendChild(errorContainer);
                
                // 嘗試重新載入LIFF SDK
                const script = document.createElement('script');
                script.src = "https://static.line-scdn.net/liff/edge/2/sdk.js";
                script.charset = "utf-8";
                script.onload = function() {
                };
                document.head.appendChild(script);
                
                return;
            }
           
            try {
                await liff.init({
                    liffId: "2007210862-GWWM0wVK"
                });
                if (liff.isLoggedIn()) {
                    // 獲取用戶資料
                    try {
                        const profile = await liff.getProfile();
                        userLineId = profile.userId;
                        
                        // 取得預約歷史
                        await fetchAppointmentHistory();
                    } catch (profileError) {
                        
                        if (profileError.message.includes("permission") || 
                            profileError.message.includes("scope")) {
                            // 權限問題，需要在LINE Developer Console設定
                            const errorContainer = document.createElement('div');
                            errorContainer.className = 'bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6';
                            errorContainer.innerHTML = `
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700 font-bold">權限不足</p>
                                        <p class="text-sm text-red-700">此LIFF應用缺少所需權限，請聯繫管理員更新設定</p>
                                        <p class="text-sm text-red-700 mt-1">錯誤訊息: ${profileError.message}</p>
                                    </div>
                                </div>
                            `;
                            document.querySelector('.container').appendChild(errorContainer);
                            document.getElementById('loadingState').classList.add('hidden');
                            
                            // 顯示手動輸入表單
                            document.getElementById('manualInputForm').classList.remove('hidden');
                            
                            // 設置提交按鈕事件
                            document.getElementById('submitManualId').addEventListener('click', function() {
                                const manualId = document.getElementById('manualLineId').value.trim();
                                if (!manualId) {
                                    showNotification('請輸入您的LINE用戶ID', 'warning');
                                    return;
                                }
                                
                                // 使用手動輸入的ID
                                userLineId = manualId;
                                document.getElementById('manualInputForm').classList.add('hidden');
                                
                                // 繼續流程
                                fetchAppointmentHistory();
                            });
                        } else {
                            // 其他錯誤
                            showNotification('無法獲取用戶資料，請稍後再試', 'error');
                            document.getElementById('loadingState').classList.add('hidden');
                        }
                    }
                } else {
                    console.log('未登入');
                    // LINE未登入，顯示登入提示
                    document.getElementById('loadingState').classList.add('hidden');
                    document.getElementById('loginMessage').classList.remove('hidden');
                    liff.login();
                }
            } catch (error) {
                
                console.error('LIFF初始化失敗', error);
                console.error('錯誤詳情:', JSON.stringify(error, null, 2));
                console.error('錯誤訊息:', error.message);
                console.error('錯誤名稱:', error.name);
                showNotification('無法初始化LINE服務，請稍後再試', 'error');
                document.getElementById('loadingState').classList.add('hidden');
                
                // 顯示更詳細的錯誤信息在頁面上，方便調試
                const errorContainer = document.createElement('div');
                errorContainer.className = 'bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6';
                errorContainer.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700 font-bold">初始化失敗</p>
                            <p class="text-sm text-red-700">錯誤訊息: ${error.message}</p>
                            <p class="text-sm text-red-700">錯誤類型: ${error.name}</p>
                        </div>
                    </div>
                `;
                document.querySelector('.container').appendChild(errorContainer);
            }
        }

        // 取得預約歷史
        async function fetchAppointmentHistory() {
            try {
                const response = await fetch('{{ route("line.appointment.history.fetch") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        line_user_id: userLineId
                    })
                });

                const result = await response.json();
                
                // 隱藏載入狀態
                document.getElementById('loadingState').classList.add('hidden');
                document.getElementById('historyContainer').classList.remove('hidden');

                if (result.success) {
                    appointments = result.appointments;
                    
                    if (appointments.length === 0) {
                        document.getElementById('noAppointments').classList.remove('hidden');
                    } else {
                        // 默認顯示即將到來的預約
                        filterAppointments('upcoming');
                    }
                } else {
                    showNotification(result.message || '無法取得預約歷史', 'error');
                }
            } catch (error) {
                console.error('取得預約歷史失敗', error);
                showNotification('發生錯誤，請稍後再試', 'error');
                document.getElementById('loadingState').classList.add('hidden');
            }
        }

        // 過濾並顯示預約
        function filterAppointments(type) {
            const now = new Date();
            const appointmentsList = document.getElementById('appointmentsList');
            appointmentsList.innerHTML = '';
            
            const filteredAppointments = appointments.filter(appointment => {
                const appointmentDate = new Date(appointment.starts_at);
                return type === 'upcoming' ? appointmentDate >= now : appointmentDate < now;
            });
            
            if (filteredAppointments.length === 0) {
                if (type === 'upcoming') {
                    appointmentsList.innerHTML = `
                        <div class="bg-white rounded-lg shadow-md p-6 text-center">
                            <div class="inline-block p-3 bg-blue-50 rounded-full mb-4">
                                <i class="fas fa-calendar-times text-blue-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">沒有即將到來的預約</h3>
                            <p class="text-gray-600">您目前沒有任何即將到來的預約</p>
                            <a href="{{ route('line.appointment') }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                                立即預約
                            </a>
                        </div>
                    `;
                } else {
                    appointmentsList.innerHTML = `
                        <div class="bg-white rounded-lg shadow-md p-6 text-center">
                            <div class="inline-block p-3 bg-blue-50 rounded-full mb-4">
                                <i class="fas fa-history text-blue-400 text-2xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2">沒有歷史預約</h3>
                            <p class="text-gray-600">您還沒有任何歷史預約記錄</p>
                        </div>
                    `;
                }
                return;
            }
            
            // 排序預約：即將到來的按時間升序，歷史的按時間降序
            filteredAppointments.sort((a, b) => {
                const dateA = new Date(a.starts_at);
                const dateB = new Date(b.starts_at);
                return type === 'upcoming' ? dateA - dateB : dateB - dateA;
            });
            
            // 生成預約卡片
            filteredAppointments.forEach(appointment => {
                const startsAt = new Date(appointment.starts_at);
                const endsAt = new Date(appointment.ends_at);
                
                const dateStr = formatDate(startsAt);
                const dayOfWeek = getDayOfWeek(startsAt);
                const timeStr = `${formatTime(startsAt)} - ${formatTime(endsAt)}`;
                
                const card = document.createElement('div');
                card.className = 'bg-white rounded-lg shadow-md p-4 mb-4 card-hover';
                card.innerHTML = `
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="badge ${type === 'upcoming' ? 'badge-green' : 'badge-blue'}">
                                ${type === 'upcoming' ? '即將到來' : '已完成'}
                            </span>
                            <span class="badge badge-yellow ml-2">
                                ${appointment.doctor ? appointment.doctor.name : '未指定醫生'}
                            </span>
                        </div>
                        <div class="text-gray-500 text-sm">
                            ${appointment.title}
                        </div>
                    </div>
                    <div class="mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">醫生：${appointment.doctor ? appointment.doctor.name : '未指定'}</h3>
                        <div class="flex items-center mt-1">
                            <i class="far fa-calendar-alt text-blue-500 mr-2"></i>
                            <span class="text-gray-600">${dateStr} (${dayOfWeek})</span>
                        </div>
                        <div class="flex items-center mt-1">
                            <i class="far fa-clock text-blue-500 mr-2"></i>
                            <span class="text-gray-600">${timeStr}</span>
                        </div>
                    </div>
                    <div class="border-t border-gray-100 pt-3">
                        <div class="text-sm">
                            <div class="font-medium text-gray-700 mb-1">症狀備註：</div>
                            <p class="text-gray-600">${appointment.patient_notes || '無備註'}</p>
                        </div>
                    </div>
                `;
                
                appointmentsList.appendChild(card);
            });
        }

        // 顯示通知訊息
        function showNotification(message, type = 'info') {
            const colors = {
                'info': 'blue',
                'success': 'green',
                'warning': 'yellow',
                'error': 'red'
            };

            const color = colors[type] || 'blue';

            // 創建通知元素
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 bg-${color}-50 border-l-4 border-${color}-400 p-4 rounded shadow-lg max-w-xs z-50 transform transition-transform duration-300 ease-in-out translate-x-full`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} text-${color}-400"></i>
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

        // 格式化日期
        function formatDate(date) {
            return `${date.getFullYear()}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getDate().toString().padStart(2, '0')}`;
        }

        // 獲取星期
        function getDayOfWeek(date) {
            const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
            return '星期' + weekdays[date.getDay()];
        }

        // 格式化時間
        function formatTime(date) {
            return `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
        }
    </script>
</body>

</html> 