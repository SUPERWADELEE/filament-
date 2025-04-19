<?php
// app/Services/AppointmentReminderService.php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use LINE\Clients\MessagingApi\Configuration;
use LINE\Clients\MessagingApi\Api\MessagingApiApi;
use LINE\Clients\MessagingApi\Model\PushMessageRequest;
use LINE\Clients\MessagingApi\Model\TextMessage;
use Illuminate\Support\Facades\Log;

class AppointmentReminderService
{
    protected MessagingApiApi $bot;
    /**
     * 通知時間（分鐘）
     * 
     * @var int
     */
    protected $notification_time = 15;

    public function __construct()
    {
        // 初始化LINE API客戶端
        $config = Configuration::getDefaultConfiguration()
            ->setAccessToken(config('services.line.channel_token'));

        $this->bot = new MessagingApiApi(null, $config);
    }

    /*
     * 發送預約提醒
     * 
     * @return int 發送的提醒數量
     */
    public function sendReminders(): int
    {
        // 查找即將到來的預約 (15分鐘內開始且未發送過提醒)
        $upcomingAppointments = $this->getIncomingEvents();

        Log::info("即將到來的預約: " . $upcomingAppointments);
        $count = 0;

        foreach ($upcomingAppointments as $appointment) {
            // 確保患者有關聯的LINE ID
            if ($appointment->patient && $appointment->patient->line_user_id) {
                try {
                    // 發送提醒
                    $this->sendReminderToPatient($appointment);

                    Log::info("發送提醒: " . $appointment);
                    // 標記提醒已發送
                    $appointment->reminder_sent_at = Carbon::now();
                    $appointment->save();

                    $count++;
                    Log::info("已發送預約提醒給患者ID: {$appointment->patient_id}, 預約ID: {$appointment->id}");
                } catch (\Exception $e) {
                    Log::error('發送LINE提醒失敗: ' . $e->getMessage());
                }
            }
        }

        return $count;
    }

    /**
     * 向患者發送提醒訊息
     */
    protected function sendReminderToPatient(Event $appointment)
    {
        // 格式化訊息內容
        $startTime = Carbon::parse($appointment->starts_at)->format('Y-m-d H:i');
        $doctorName = $appointment->doctor ? $appointment->doctor->name : '醫生';

        $message = "您好！提醒您在 {$startTime}（約15分鐘後）有預約 {$doctorName} 的診療。";
        if ($appointment->location) {
            $message .= "診間位置：{$appointment->location}";
        }

        // 創建LINE訊息
        $textMessage = new TextMessage([
            'type' => 'text',
            'text' => $message,
        ]);

        // 發送訊息到LINE
        $pushRequest = new PushMessageRequest([
            'to' => $appointment->patient->line_user_id,
            'messages' => [$textMessage],
        ]);

        $this->bot->pushMessage($pushRequest);
    }

    /**
     * 獲取即將到來的預約
     * 
     * @return Collection
     */
    protected function getIncomingEvents()
    {
        $upcomingAppointments = Event::where('status', 'booked')
            ->where('starts_at', '>', Carbon::now())
            ->where('starts_at', '<=', Carbon::now()->addMinutes($this->notification_time))
            ->whereNull('reminder_sent_at')
            ->with(['patient', 'doctor'])
            ->get();

        return $upcomingAppointments;
    }
}
