<div>
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

    <div class="text-center mb-10">
        <div class="inline-block p-3 bg-blue-100 rounded-full mb-4">
            <i class="fas fa-hospital-user text-blue-500 text-4xl"></i>
        </div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">線上診療預約</h1>
        <p class="text-gray-600">請填寫以下資料完成您的診療預約</p>
        <div class="mt-3">
            <a href="{{ route('line.appointment.history') }}" class="text-blue-500 hover:text-blue-700">
                <i class="fas fa-history mr-1"></i>查看預約歷史
            </a>
        </div>
    </div>

    @if($showLoginMessage)
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg mb-6">
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
    @endif

    @if($error)
    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">
                    {{ $error }}
                </p>
            </div>
        </div>
    </div>
    @endif

    @if($debugInfo)
    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-info-circle text-blue-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    {{ $debugInfo }}
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- 載入中狀態 --}}
    @if($loading)
    <div class="text-center py-10">
        <div class="inline-block loading-spinner h-12 w-12 border-4 border-blue-200 border-t-blue-500 rounded-full"></div>
        <p class="mt-4 text-gray-600">載入中，請稍候...</p>
    </div>
    @else
    @if(!$showSuccessCard)
    <!-- 預約表單 -->
    <div class="bg-white rounded-xl shadow-xl overflow-hidden">
        <!-- 表單頂部裝飾 -->
        <div class="h-2 bg-gradient-to-r from-blue-400 to-indigo-500"></div>

        <div class="p-8">
            <!-- 姓名輸入 -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="patientName">
                    <i class="fas fa-user text-blue-500 mr-2"></i>您的姓名
                </label>
                <input wire:model.defer="patientName" type="text" id="patientName"
                    class="form-input w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors
                        @error('patientName') border-red-500 @enderror"
                    placeholder="請輸入您的姓名">
                <p class="text-sm text-gray-500 mt-1">*若不修改，將使用您的LINE帳號名稱</p>
                @error('patientName')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- 醫生選擇 -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="selectedDoctorId">
                    <i class="fas fa-user-md text-blue-500 mr-2"></i>選擇醫生
                </label>
                <div class="relative">
                    <select wire:model.live="selectedDoctorId" id="selectedDoctorId"
                        class="form-select custom-select w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors
                            @error('selectedDoctorId') border-red-500 @enderror">
                        <option value="">請選擇醫生</option>
                        @foreach($availableDoctors as $doctor)
                        <option value="{{ $doctor['id'] }}">{{ $doctor['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                @error('selectedDoctorId')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- 時段選擇 -->
            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="selectedTimeSlotId">
                    <i class="far fa-clock text-blue-500 mr-2"></i>選擇時段
                </label>
                <div class="relative">
                    <select wire:model.defer="selectedTimeSlotId" id="selectedTimeSlotId"
                        class="form-select custom-select w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors
                            @error('selectedTimeSlotId') border-red-500 @enderror"
                        @if(empty($availableTimeSlots)) disabled @endif>
                        <option value="">{{ empty($availableTimeSlots) ? '請先選擇醫生' : '請選擇時段' }}</option>
                        @foreach($availableTimeSlots as $slot)
                        <option value="{{ $slot['id'] }}">
                            {{ $this->formatDate($slot['starts_at']) }} ({{ $this->getDayOfWeek($slot['starts_at']) }})
                            {{ $this->formatTime($slot['starts_at']) }} - {{ $this->formatTime($slot['ends_at']) }}
                            @if(isset($slot['location']) && !empty($slot['location']))
                             • {{ $slot['location'] }}
                            @endif
                        </option>
                        @endforeach
                    </select>
                </div>
                @error('selectedTimeSlotId')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- 症狀描述 -->
            <div class="mb-8">
                <label class="block text-gray-700 font-semibold mb-2" for="patientNotes">
                    <i class="fas fa-notes-medical text-blue-500 mr-2"></i>症狀描述
                </label>
                <textarea wire:model.defer="patientNotes" id="patientNotes"
                    class="form-textarea w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 transition-colors"
                    rows="4"
                    placeholder="請描述您的症狀或需求，幫助醫生提前了解您的情況"></textarea>
            </div>

            @error('form')
            <div class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ $message }}</p>
                    </div>
                </div>
            </div>
            @enderror

            <!-- 提交按鈕 -->
            <button wire:click="submitBooking" wire:loading.attr="disabled" wire:loading.class="opacity-70"
                class="btn-transition w-full bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-medium py-3 px-4 rounded-lg hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-50 shadow-md">
                <span wire:loading.remove wire:target="submitBooking">
                    <i class="fas fa-calendar-check mr-2"></i>確認預約
                </span>
                <span wire:loading wire:target="submitBooking">
                    <i class="fas fa-spinner fa-spin mr-2"></i>處理中...
                </span>
            </button>
        </div>
    </div>
    @else
    <!-- 預約成功的確認卡片 -->
    <div class="mt-6 bg-white rounded-xl shadow-lg p-6">
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
    @endif
    @endif

</div>