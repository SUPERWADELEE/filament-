<div>
    {{-- 狀態提示區域 --}}
    @if($showLoginMessage)
    <x-line.alert type="warning" icon="exclamation-circle">
        請先登入LINE以查看您的預約歷史
    </x-line.alert>
    @endif


    @if($sdkLoadError)
    <x-line.alert type="error" icon="exclamation-triangle" title="LINE SDK未載入">
        {{ $error }}
    </x-line.alert>
    @endif

    @if($error && !$sdkLoadError)
    <x-line.alert type="error" icon="exclamation-triangle" title="發生錯誤">
        {{ $error }}
    </x-line.alert>
    @endif

    {{-- 載入中狀態 --}}
    @if($loading)
    <div class="text-center py-10">
        <div class="inline-block loading-spinner h-12 w-12 border-4 border-blue-200 border-t-blue-500 rounded-full"></div>
        <p class="mt-4 text-gray-600">載入中，請稍候...</p>
    </div>
    @else
    {{-- 預約歷史列表 --}}
    <div class="mb-6">
        {{-- 切換標籤 --}}
        <div class="flex mb-4">
            <button wire:click="changeTab('upcoming')"
                class="flex-1 py-2 px-4 text-center {{ $activeTab == 'upcoming' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }} rounded-tl-lg font-medium">
                即將到來
            </button>
            <button wire:click="changeTab('past')"
                class="flex-1 py-2 px-4 text-center {{ $activeTab == 'past' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700' }} rounded-tr-lg font-medium">
                歷史記錄
            </button>
        </div>

        {{-- 無預約提示 --}}
        @if(($activeTab == 'upcoming' && empty($upcomingAppointments)) || ($activeTab == 'past' && empty($pastAppointments)))
        <div class="bg-white rounded-lg shadow-md p-6 text-center">
            <div class="inline-block p-3 bg-blue-50 rounded-full mb-4">
                <i class="fas fa-{{ $activeTab == 'upcoming' ? 'calendar-times' : 'history' }} text-blue-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-800 mb-2">沒有{{ $activeTab == 'upcoming' ? '即將到來的預約' : '歷史預約' }}</h3>
            <p class="text-gray-600">您{{ $activeTab == 'upcoming' ? '目前沒有任何即將到來的預約' : '還沒有任何歷史預約記錄' }}</p>
            @if($activeTab == 'upcoming')
            <a href="{{ route('line.appointment') }}" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition">
                立即預約
            </a>
            @endif
        </div>
        @else
        {{-- 預約列表 --}}
        <div>
            @foreach(($activeTab == 'upcoming' ? $upcomingAppointments : $pastAppointments) as $appointment)
            <div class="bg-white rounded-lg shadow-md p-4 mb-4 card-hover">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <span class="badge {{ $activeTab == 'upcoming' ? 'badge-green' : 'badge-blue' }}">
                            {{ $activeTab == 'upcoming' ? '即將到來' : '已完成' }}
                        </span>
                        <span class="badge badge-yellow ml-2">
                            {{ isset($appointment['doctor']['name']) ? $appointment['doctor']['name'] : '未指定醫生' }}
                        </span>
                    </div>
                    <div class="text-gray-500 text-sm">
                        {{ $appointment['title'] ?? '診療預約' }}
                    </div>
                </div>
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">醫生：{{ isset($appointment['doctor']['name']) ? $appointment['doctor']['name'] : '未指定' }}</h3>
                    <div class="flex items-center mt-1">
                        <i class="far fa-calendar-alt text-blue-500 mr-2"></i>
                        <span class="text-gray-600">{{ $this->formatDate($appointment['starts_at']) }} ({{ $this->getDayOfWeek($appointment['starts_at']) }})</span>
                    </div>
                    <div class="flex items-center mt-1">
                        <i class="far fa-clock text-blue-500 mr-2"></i>
                        <span class="text-gray-600">{{ $this->formatTime($appointment['starts_at']) }} - {{ $this->formatTime($appointment['ends_at']) }}</span>
                    </div>
                </div>
                <div class="border-t border-gray-100 pt-3">
                    <div class="text-sm">
                        <div class="font-medium text-gray-700 mb-1">症狀備註：</div>
                        <p class="text-gray-600">{{ $appointment['patient_notes'] ?? '無備註' }}</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

</div>