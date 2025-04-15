<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use App\Mail\VerifyEmail;

class CreateUser extends CreateRecord
{

    protected static string $resource = UserResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array
    // {
    //     // 修改並記錄數據
    //     $data['verification_token'] = Str::random(40);
    //     $data['email_verified_at'] = null;

    //     return $data;
    // }

    // protected function afterCreate(): void
    // {
    //     // 生成驗證連結
    //     $verificationUrl = URL::temporarySignedRoute(
    //         'verification.verify',
    //         now()->addDays(7),
    //         [
    //             'id' => $this->record->id,
    //             'token' => $this->record->verification_token,
    //         ]
    //     );
    //     try {
    //         Mail::to($this->record->email)->send(new VerifyEmail($this->record, $verificationUrl));
    //         // 顯示通知成功
    //         Notification::make()
    //             ->title('用戶已創建')
    //             ->body('驗證郵件已發送至用戶的電子郵件')
    //             ->success()
    //             ->send();
    //     } catch (\Exception $e) {
    //         // 處理郵件發送失敗
    //         Notification::make()
    //             ->title('用戶已創建，但郵件發送失敗')
    //             ->body('錯誤: ' . $e->getMessage())
    //             ->danger()
    //             ->send();

    //         // 記錄錯誤
    //         \Illuminate\Support\Facades\Log::error('發送驗證郵件失敗', [
    //             'user_id' => $this->record->id,
    //             'email' => $this->record->email,
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }
}
