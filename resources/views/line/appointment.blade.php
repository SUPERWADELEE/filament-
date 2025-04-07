<!-- resources/views/line/appointment.blade.php -->
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
    <style>
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
        }

        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .btn-transition {
            transition: all 0.3s ease;
        }

        .btn-transition:hover {
            transform: translateY(-2px);
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

        .custom-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-blue-50 to-indigo-50 min-h-screen">
    <div class="container mx-auto px-4 py-12 max-w-lg">
        <div class="text-center mb-10">
            <div class="inline-block p-3 bg-blue-100 rounded-full mb-4">
                <i class="fas fa-hospital-user text-blue-500 text-4xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">線上診療預約</h1>
            <p class="text-gray-600">請填寫以下資料完成您的診療預約</p>
        </div>

        <div id="loginMessage" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-6 hidden">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        請先登入LINE以使用預約功能
                    </p>
                </div>
            </div>
        </div>

        <div id="loadingState" class="text-center py-10 hidden">
            <div class="inline-block loading-spinner h-12 w-12 border-4 border-blue-200 border-t-blue-500 rounded-full"></div>
            <p class="mt-4 text-gray-600">載入中，請稍候...</p>
        </div>

        <!-- 預約表單 -->
        <div id="bookingForm" class="bg-white rounded-xl shadow-xl overflow-hidden">
            <!-- 表單頂部裝飾 -->
            <div class="h-2 bg-gradient-to-r from-blue-400 to-indigo-500"></div>

            <div class="p-8">
                <!-- 姓名輸入 -->
                <!-- 姓名輸入 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2" for="patientName">
                        <i class="fas fa-user text-blue-500 mr-2"></i>您的姓名
                    </label>
                    <input type="text" id="patientName"
                        class="form-input w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"
                        placeholder="請輸入您的姓名">
                    <p class="text-sm text-gray-500 mt-1">*若不修改，將使用您的LINE帳號名稱</p>
                </div>

                <!-- 醫生選擇 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2" for="doctorSelect">
                        <i class="fas fa-user-md text-blue-500 mr-2"></i>選擇醫生
                    </label>
                    <div class="relative">
                        <select id="doctorSelect"
                            class="form-select custom-select w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors">
                            <option value="">請選擇醫生</option>
                        </select>
                    </div>
                </div>

                <!-- 時段選擇 -->
                <div class="mb-6">
                    <label class="block text-gray-700 font-semibold mb-2" for="timeSlotSelect">
                        <i class="far fa-clock text-blue-500 mr-2"></i>選擇時段
                    </label>
                    <div class="relative">
                        <select id="timeSlotSelect"
                            class="form-select custom-select w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"
                            disabled>
                            <option value="">請先選擇醫生</option>
                        </select>
                    </div>
                </div>

                <!-- 症狀描述 -->
                <div class="mb-8">
                    <label class="block text-gray-700 font-semibold mb-2" for="patientNotes">
                        <i class="fas fa-notes-medical text-blue-500 mr-2"></i>症狀描述
                    </label>
                    <textarea id="patientNotes"
                        class="form-textarea w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"
                        rows="4"
                        placeholder="請描述您的症狀或需求，幫助醫生提前了解您的情況"></textarea>
                </div>

                <input type="hidden" id="lineUserId">

                <!-- 提交按鈕 -->
                <button id="submitBooking"
                    class="btn-transition w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50 shadow-md">
                    <i class="fas fa-calendar-check mr-2"></i>確認預約
                </button>
            </div>
        </div>

        <!-- 預約成功的確認卡片 (默認隱藏) -->
        <div id="successCard" class="mt-6 bg-white rounded-xl shadow-lg p-6 hidden">
            <div class="text-center mb-4">
                <div class="inline-flex items-center justify-center h-12 w-12 rounded-full bg-green-100 text-green-500 mb-3">
                    <i class="fas fa-check text-xl"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">預約成功</h3>
                <p class="text-gray-500 mt-1">您的預約申請已成功送出</p>
            </div>

            <div class="border-t border-gray-200 mt-4 pt-4">
                <button id="closeButton"
                    class="w-full mt-3 bg-blue-500 text-white font-medium py-2 px-4 rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50">
                    返回LINE
                </button>
            </div>
        </div>

        <!-- 底部資訊 -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>若有任何問題，請聯繫我們的客服</p>
            <p class="mt-1">&copy; 2023 醫療預約系統</p>
        </div>
    </div>

    <script>
        // 顯示載入狀態
        document.getElementById('loadingState').classList.remove('hidden');
        document.getElementById('bookingForm').classList.add('hidden');

        // 初始化 LIFF
        document.addEventListener('DOMContentLoaded', async function() {
            await initializeLiff();
            await initializeForm();

            // 隱藏載入狀態
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('bookingForm').classList.remove('hidden');
        });

        // 初始化 LIFF
        async function initializeLiff() {
            // 創建一個調試區域
            const debugArea = document.createElement('div');
            debugArea.style.position = 'fixed';
            debugArea.style.bottom = '0';
            debugArea.style.right = '0';
            debugArea.style.backgroundColor = 'rgba(0,0,0,0.7)';
            debugArea.style.color = 'white';
            debugArea.style.padding = '10px';
            debugArea.style.fontSize = '12px';
            debugArea.style.maxHeight = '150px';
            debugArea.style.overflow = 'auto';
            debugArea.style.zIndex = '9999';
            document.body.appendChild(debugArea);

            function debug(message) {
                console.log(message);
                const line = document.createElement('div');
                line.textContent = message;
                debugArea.appendChild(line);
                debugArea.scrollTop = debugArea.scrollHeight;
            }

            debug("初始化LIFF...");
            try {
                await liff.init({
                    liffId: "2007210862-ZL3R8Jy4"
                });
                debug("LIFF初始化成功");

                if (liff.isLoggedIn()) {
                    // 只是獲取LINE資料的前提條件
                    const profile = await liff.getProfile();
                    const lineUserId = profile.userId;
                    debug("LINE ID:", lineUserId);

                    // 真正重要的是：檢查該LINE ID是否有對應的Laravel帳號
                    await checkOrCreateUser(profile.userId, profile.displayName);
                } else {
                    // LINE未登入，無法獲取用戶資料，需要先登入LINE
                    liff.login();
                }
            } catch (error) {
                debug('LIFF初始化失敗: ' + error.message);
                showNotification('無法初始化LINE服務，請稍後再試', 'error');
            }
        }


        async function checkOrCreateUser(lineUserId, displayName) {
            try {
                const response = await fetch('{{ route("line.check.user") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        line_user_id: lineUserId,
                        display_name: displayName
                    })
                });

                const result = await response.json();
                if (result.success) {
                    console.log('用戶已驗證:', result.is_new_user ? '新用戶' : '現有用戶');
                }
            } catch (error) {
                console.error('用戶驗證失敗', error);
            }
        }
        // 初始化表單
        async function initializeForm() {
            console.log("初始化表單...");
            try {
                // 獲取所有事件
                const events = @json($events ?? []);
                console.log("獲取到事件數量:", events.length);

                if (!events || events.length === 0) {
                    showNotification('目前沒有可預約的時段', 'info');
                    return;
                }

                // 提取唯一醫生列表
                const doctors = [...new Map(
                    events.filter(event => {
                        console.log("檢查事件:", event);
                        return event.doctor && event.status === 'available';
                    })
                    .map(event => [event.doctor.id, event.doctor])
                ).values()];;

                // 填充醫生下拉選單
                const doctorSelect = document.getElementById('doctorSelect');

                // 清空現有選項，只保留第一個預設選項
                while (doctorSelect.options.length > 1) {
                    doctorSelect.remove(1);
                }

                doctors.forEach(doctor => {
                    const option = document.createElement('option');
                    option.value = doctor.id;
                    option.textContent = doctor.name;
                    doctorSelect.appendChild(option);
                });

                // 如果沒有可用醫生，顯示提示
                if (doctors.length === 0) {
                    console.log("沒有可用醫生");
                    const option = document.createElement('option');
                    option.value = "";
                    option.textContent = "目前沒有可預約的醫生";
                    doctorSelect.appendChild(option);
                    doctorSelect.disabled = true;
                }

                // 監聽醫生選擇變化
                doctorSelect.addEventListener('change', function() {
                    const doctorId = this.value;
                    updateTimeSlots(doctorId, events);
                });

                // 提交按鈕事件
                document.getElementById('submitBooking').addEventListener('click', submitBooking);

                // 關閉按鈕事件
                document.getElementById('closeButton').addEventListener('click', function() {
                    liff.closeWindow();
                });
            } catch (error) {
                console.error('表單初始化失敗', error);
                showNotification('載入醫生資料失敗，請重新整理頁面', 'error');
            }
        }

        // 更新時段下拉選單
        function updateTimeSlots(doctorId, events) {
            console.log("更新時段，醫生ID:", doctorId);
            const timeSlotSelect = document.getElementById('timeSlotSelect');

            // 清空現有選項
            timeSlotSelect.innerHTML = '';
            timeSlotSelect.appendChild(new Option('請選擇時段', ''));

            if (!doctorId) {
                timeSlotSelect.disabled = true;
                return;
            }

            // 過濾選定醫生的可用時段
            const availableSlots = events.filter(event =>
                event.doctor &&
                event.doctor.id == doctorId &&
                event.status === 'available'
            );

            console.log("該醫生可用時段數量:", availableSlots.length);

            if (availableSlots.length === 0) {
                timeSlotSelect.appendChild(new Option('該醫生目前沒有可用時段', ''));
                timeSlotSelect.disabled = true;
                return;
            }

            // 將時段按日期和時間排序
            availableSlots.sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at));

            // 添加時段選項，並按日期分組
            let currentDate = '';

            availableSlots.forEach(slot => {
                const startsAt = new Date(slot.starts_at);
                const endsAt = new Date(slot.ends_at);

                const dateStr = formatDate(startsAt);

                // 如果是新的日期，添加分隔符
                if (dateStr !== currentDate) {
                    currentDate = dateStr;

                    const groupOption = document.createElement('option');
                    groupOption.disabled = true;
                    groupOption.style.fontWeight = 'bold';
                    groupOption.style.backgroundColor = '#f3f4f6';
                    groupOption.textContent = `${dateStr} (${getDayOfWeek(startsAt)})`;
                    timeSlotSelect.appendChild(groupOption);
                }

                const timeText = `${formatTime(startsAt)}-${formatTime(endsAt)} ${slot.title}`;

                const option = document.createElement('option');
                option.value = slot.id;
                option.textContent = timeText;
                timeSlotSelect.appendChild(option);
            });

            timeSlotSelect.disabled = false;
        }

        // 提交預約
        async function submitBooking() {
            const patientName = document.getElementById('patientName').value;
            const eventId = document.getElementById('timeSlotSelect').value;
            const patientNotes = document.getElementById('patientNotes').value;
            const lineUserId = document.getElementById('lineUserId').value;

            // 表單驗證
            if (!patientName) {
                showNotification('請輸入您的姓名', 'warning');
                return;
            }

            if (!eventId) {
                showNotification('請選擇預約時段', 'warning');
                return;
            }

            // 顯示載入中狀態
            const submitBtn = document.getElementById('submitBooking');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>處理中...';

            try {
                const response = await fetch('{{ route("line.appointment.book") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        event_id: eventId,
                        patient_name: patientName,
                        patient_notes: patientNotes,
                        line_user_id: lineUserId
                    })
                });

                const result = await response.json();

                // 恢復按鈕狀態
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;

                if (result.success) {
                    // 隱藏表單，顯示成功信息
                    document.getElementById('bookingForm').classList.add('hidden');
                    document.getElementById('successCard').classList.remove('hidden');
                } else if (result.needRegistration) {
                    showNotification('需要綁定LINE帳號', 'warning');
                } else {
                    showNotification(result.message || '預約失敗，請稍後再試', 'error');
                }
            } catch (error) {
                console.error('預約請求錯誤', error);
                showNotification('發生錯誤，請稍後再試', 'error');

                // 恢復按鈕狀態
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
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