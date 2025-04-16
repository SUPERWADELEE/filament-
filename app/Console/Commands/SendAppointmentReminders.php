<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppointmentReminderService;

class SendAppointmentReminders extends Command
{
    protected $signature = 'appointments:send-reminders';

    public function handle(AppointmentReminderService $reminderService)
    {
        $this->info('開始發送預約提醒...');
        $count = $reminderService->sendReminders();
        $this->info("成功發送 {$count} 條提醒");
        
        return Command::SUCCESS;
    }
}