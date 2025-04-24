<?php

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
    public function sendReminders()
    {
        $upcomingAppointments = $this->getIncomingEvents();
        $count = 0;
        $sentAppointmentIds = [];

        // 對每個預約單獨處理，成功發送後立即標記
        foreach ($upcomingAppointments as $appointment) {
            if ($appointment->patient && $appointment->patient->line_user_id) {
                try {
                    // 發送提醒
                    $this->sendReminderToPatient($appointment);
                    $sentAppointmentIds[] = $appointment->id;
                    $count++;
                } catch (\Exception $e) {
                    Log::error('發送LINE失敗: ' . $e->getMessage(), [
                        'appointment_id' => $appointment->id,
                        'patient_id' => $appointment->patient_id
                    ]);
                }
            }
        }
        // 批量更新
        if (!empty($sentAppointmentIds)) {
            Event::whereIn('id', $sentAppointmentIds)->update(['reminder_sent_at' => Carbon::now()]);
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

        $message = "您好！提醒您在 {$startTime}（約{$this->notification_time}分鐘後）有預約 {$doctorName} 的診療。";
        if ($appointment->location) {
            $message .= "診間位置：{$appointment->location}";
        }

        // 創建符合LINE規格訊息
        $textMessage = new TextMessage([
            'type' => 'text',
            'text' => $message
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
    public function setBot(MessagingApiApi $bot)
    {
        $this->bot = $bot;
        return $this;
    }
}
