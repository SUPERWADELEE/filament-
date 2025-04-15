<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;

class Register extends BaseRegister
{
    // 添加一個屬性來存儲用戶郵箱
    // 註冊送出的後端邏輯

    // public $registeredUserEmail = null;

    // 這裏主要儲存註冊的email,方便發送驗證信時可以知道要發給誰
    // public function register(): ?RegistrationResponse
    // {
    //     // 獲取並保存郵箱
    //     $email = $this->form->getState()['email'];
    //     $this->registeredUserEmail = $email;

    //     // 執行父類的註冊邏輯，但不執行其返回的響應
    //     $response = null;

    //     try {
    //         // 執行父類的註冊流程，但捕獲響應而不立即返回
    //         $response = parent::register();

    //         // 使用保存的郵箱獲取用戶
    //         $user = User::where('email', $this->registeredUserEmail)->first();
    //         if (!$user) {
    //             Notification::make()
    //                 ->title('錯誤')
    //                 ->body('無法獲取用戶資訊')
    //                 ->danger()
    //                 ->send();

    //             return $response;
    //         }

    //         // 設置驗證令牌
    //         $user->verification_token = Str::random(40);
    //         $user->email_verified_at = null; // 確保未驗證
    //         $user->save();

    //         // 生成驗證連結
    //         $verificationUrl = URL::temporarySignedRoute(
    //             'verification.verify',
    //             now()->addDays(7),
    //             [
    //                 'id' => $user->id,
    //                 'token' => $user->verification_token,
    //             ]
    //         );

    //         // 發送驗證郵件
    //         Mail::to($user->email)->send(new VerifyEmail($user, $verificationUrl));

    //         // 顯示通知
    //         Notification::make()
    //             ->title('註冊成功')
    //             ->body('請檢查您的電子郵件以完成驗證')
    //             ->success()
    //             ->send();

    //         // 這裡確保登出用戶

    //         Auth::logout();
    //     } catch (\Exception $e) {
    //         // 處理可能的異常
    //         Notification::make()
    //             ->title('錯誤')
    //             ->body($e->getMessage())
    //             ->danger()
    //             ->send();
    //     }

    //     return $response;
    // }
}