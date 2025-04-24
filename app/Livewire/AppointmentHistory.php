<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Http\Controllers\LineAppointmentController;

class AppointmentHistory extends Component
{
    public $appointments = [];
    public $upcomingAppointments = [];
    public $pastAppointments = [];
    public $activeTab = 'upcoming';
    public $loading = true;
    public $error = null;
    public $lineUserId = null;
    public $showLoginMessage = false;
    public $sdkLoadError = false;
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

    public function mount($lineUserId = null)
    {
        $this->lineUserId = $lineUserId;

        // 如果已有 lineUserId，則直接獲取預約
        if ($this->lineUserId) {
            $this->fetchAppointments();
        }
    }

    // 當 LINE 用戶資料載入時
    public function lineUserProfileLoaded($data = null)
    {
        try {
            // 處理直接傳入的字符串
            $userId = $data;
            if ($userId && is_string($userId)) {
                $this->lineUserId = $userId;
                $this->fetchAppointments();
            } else {
                $this->showLoginMessage = true;
                $this->handleError('無法獲取LINE用戶資料');
            }
        } catch (\Exception $e) {
            Log::error('處理LINE用戶數據錯誤: ' . $e->getMessage());
            $this->handleError('用戶資料處理錯誤');
        }
    }

    // 當LIFF初始化失敗
    public function liffInitError($errorMessage)
    {
        $this->sdkLoadError = true;
        $this->loading = false;
        $this->error = "LINE SDK 載入失敗: " . $errorMessage;
    }

    // 當需要LINE登入
    public function lineLoginRequired()
    {
        $this->showLoginMessage = true;
        $this->loading = false;
    }

    // 獲取預約資料
    public function fetchAppointments()
{
    $this->loading = true;
    try {
        if (!$this->lineUserId) {
            $this->showLoginMessage = true;
            $this->loading = false;
            return;
        }
        
        // 創建請求對象
        $request = new Request([
            'line_user_id' => $this->lineUserId,
        ]);
        
        // 調用控制器方法
        $controller = new LineAppointmentController();
        $response = $controller->getAppointmentsByLineUserId($request);
        
        // 解析響應
        $data = json_decode($response->getContent(), true);
        
        if (isset($data['success']) && $data['success']) {
            // 更新組件數據
            $this->upcomingAppointments = $data['upcomingAppointments'];
            $this->pastAppointments = $data['pastAppointments'];
            $this->loading = false;
        } else {
            $this->showLoginMessage = true;
            $this->loading = false;
            $this->dispatch('showNotification', [
                'message' => $data['message'] ?? '無法獲取預約資料',
                'type' => 'warning'
            ]);
        }
    } catch (\Exception $e) {
        Log::error('AppointmentHistory fetchAppointments error', ['error' => $e->getMessage()]);
        $this->handleError('載入預約記錄時發生錯誤');
    }
}

    // 分類預約為即將到來和過去的
    protected function categorizeAppointments($appointments, $now)
    {
        // 即將到來的預約 (狀態為booked，且時間在當前之後)
        $this->upcomingAppointments = $appointments->filter(function ($appointment) use ($now) {
            return $appointment->status === 'booked' && $appointment->starts_at >= $now;
        })->sortBy('starts_at')->values()->toArray();

        // 歷史預約 (狀態為finished)
        $this->pastAppointments = $appointments->filter(function ($appointment) {
            return $appointment->status === 'finished';
        })->sortByDesc('starts_at')->values()->toArray();
    }

    // 處理錯誤
    protected function handleError($message)
    {
        $this->loading = false;
        $this->error = $message;
    }

    // 切換頁籤
    public function changeTab($tab)
    {
        if (in_array($tab, ['upcoming', 'past'])) {
            $this->activeTab = $tab;
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
        return view('livewire.appointment-history');
    }
    public function updateAppointmentStatusToFinished($user, $now)
    {
        // 自動將已過期但未標記為完成的預約更新為完成狀態
        $expiredAppointmentsCount = Event::where('patient_id', $user->id)
            ->where('status', 'booked')
            ->where('starts_at', '<', $now)
            ->count();

        if ($expiredAppointmentsCount > 0) {
            Event::where('patient_id', $user->id)
                ->where('status', 'booked')
                ->where('starts_at', '<', $now)
                ->update(['status' => 'finished']);
        }
    }
    public function getAppointments($user)
    {
        return Event::where('patient_id', $user->id)
            ->whereIn('status', ['booked', 'finished'])
            ->with('doctor')
            ->get();
    }
}

