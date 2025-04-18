<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;
use App\Http\Controllers\LineAppointmentController;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Request;

class Appointment extends Component
{
    // 表單欄位
    public $patientName = '';
    public $selectedDoctorId = '';
    public $selectedTimeSlotId = '';
    public $patientNotes = '';
    public $lineUserId = null;
    public $displayName = null;

    // 頁面狀態管理
    public $loading = true;
    public $showLoginMessage = false;
    public $showSuccessCard = false;
    public $formSubmitting = false;
    public $error = null;
    public $debugInfo = null;

    // 資料儲存
    public $availableDoctors = [];
    public $availableTimeSlots = [];
    public $allEvents = [];
    public $weekdays = ['日', '一', '二', '三', '四', '五', '六'];

    // 定義事件監聽
    protected function getListeners()
    {
        return [
            'lineUserProfileLoaded',
            'lineLoginRequired',
            'liffInitError',
        ];
    }

    public function mount()
    {
        $this->loading = true;
        $this->loadAvailableEvents();
    }

    // 當 LINE 用戶資料載入時
    public function lineUserProfileLoaded($data = null)
    {
        try {
            $this->lineUserId = $data;

            if ($this->lineUserId && is_string($this->lineUserId)) {
                // 使用 Controller 方法
                $this->checkOrCreateUser($this->lineUserId, $this->displayName);
                $this->patientName = $this->displayName ?? '';
                $this->loading = false;
            } else {
                $this->showLoginMessage = true;
                $this->error = '無法獲取LINE用戶資料';
                $this->loading = false;
            }
        } catch (\Exception $e) {
            Log::error('處理LINE用戶數據錯誤: ' . $e->getMessage());
            $this->error = '用戶資料處理錯誤';
            $this->loading = false;
        }
    }

    // 當需要 LINE 登入
    public function lineLoginRequired()
    {
        $this->showLoginMessage = true;
        $this->loading = false;
    }

    // 當 LIFF 初始化失敗
    public function liffInitError($errorMessage)
    {
        $this->loading = false;
        $this->error = "LINE SDK 載入失敗: " . $errorMessage;
    }

    // 載入可用預約時段
    private function loadAvailableEvents()
    {
        try {
            // 獲取所有可用的預約時段
            $events = Event::where('status', 'available')
                ->where('starts_at', '>', now())
                ->with('doctor')
                ->get();

            // 將結果轉為陣列並儲存
            $this->allEvents = $events->toArray();

            // 如果沒有可用時段，設置提示訊息
            if (empty($this->allEvents)) {
                $this->debugInfo = '目前沒有可用的預約時段，請稍後再試。';
                return;
            }

            // 提取唯一醫生列表（直接從查詢結果中提取）
            $doctors = collect($this->allEvents)
                ->filter(function ($event) {
                    return isset($event['doctor']) && $event['status'] === 'available';
                })
                ->pluck('doctor')
                ->unique('id')
                ->values()
                ->toArray();

            $this->availableDoctors = $doctors;

            // 如果沒有醫生，設置提示訊息
            if (empty($this->availableDoctors)) {
                $this->debugInfo = '目前沒有可用的醫生，請稍後再試。';
            }
        } catch (\Exception $e) {
            Log::error('載入預約時段錯誤: ' . $e->getMessage());
            $this->error = '載入可用時段失敗';
        }
    }

    // 當醫生選擇變更時 - 這個會被 wire:model.live 自動觸發
    public function updatedSelectedDoctorId($value)
    {
        // 直接調用更新時段方法
        $this->updateTimeSlots();
    }

    // 更新時段下拉選單
    public function updateTimeSlots()
    {
        // 過濾選定醫生的可用時段
        $timeSlots = collect($this->allEvents)
            ->filter(function ($event) {
                $hasDoctor = isset($event['doctor']);
                $doctorMatches = $hasDoctor && $event['doctor']['id'] == $this->selectedDoctorId;
                $isAvailable = $event['status'] === 'available';
                return $hasDoctor && $doctorMatches && $isAvailable;
            })
            ->sortBy('starts_at')
            ->values()
            ->toArray();

        $this->availableTimeSlots = $timeSlots;
    }

    // 檢查或創建用戶
    private function checkOrCreateUser($lineUserId, $displayName = null)
    {
        try {
            // 如果沒有顯示名稱，無法創建用戶
            if (!$displayName) {
                return null;
            }

            // 創建模擬請求對象
            $request = new Request([
                'line_user_id' => $lineUserId,
                'display_name' => $displayName
            ]);

            // 使用 Controller 方法
            $controller = new LineAppointmentController();
            $controller->checkOrCreateUser($request);
        } catch (\Exception $e) {
            Log::error('檢查或創建用戶錯誤: ' . $e->getMessage());
            return null;
        }
    }

    // 提交預約
    public function submitBooking()
    {
        $this->validate([
            'patientName' => 'required|string',
            'selectedTimeSlotId' => 'required',
        ], [
            'patientName.required' => '請輸入您的姓名',
            'selectedTimeSlotId.required' => '請選擇預約時段',
        ]);

        $this->formSubmitting = true;

        try {
            // 創建模擬請求對象
            $request = new Request([
                'event_id' => $this->selectedTimeSlotId,
                'patient_name' => $this->patientName,
                'patient_notes' => $this->patientNotes,
                'line_user_id' => $this->lineUserId
            ]);

            // 使用 Controller 方法
            $controller = new LineAppointmentController();
            $response = $controller->book($request);

            // 解析回應
            $data = json_decode($response->getContent(), true);

            if (isset($data['success']) && $data['success']) {
                // 顯示成功訊息
                $this->showSuccessCard = true;
                $this->formSubmitting = false;
                // 預約成功事件回傳
                $this->dispatch('appointmentCreated', [
                    'message' => $data['message'] ?? '預約成功',
                    'eventId' => $data['event']['id'] ?? null
                ]);
            } else {
                $this->addError('form', $data['message'] ?? '預約失敗，請稍後再試');
                $this->formSubmitting = false;
            }
        } catch (\Exception $e) {
            Log::error('預約提交錯誤: ' . $e->getMessage());
            $this->addError('form', '預約失敗，請稍後再試');
            $this->formSubmitting = false;
        }
    }

    // 格式化日期
    public function formatDate($date)
    {
        $carbonDate = Carbon::parse($date);
        return $carbonDate->format('Y/m/d');
    }

    // 獲取星期
    public function getDayOfWeek($date)
    {
        $carbonDate = Carbon::parse($date);
        return '星期' . $this->weekdays[$carbonDate->dayOfWeek];
    }

    // 格式化時間
    public function formatTime($date)
    {
        $carbonDate = Carbon::parse($date);
        return $carbonDate->format('H:i');
    }

    public function render()
    {
        return view('livewire.appointment');
    }
}
