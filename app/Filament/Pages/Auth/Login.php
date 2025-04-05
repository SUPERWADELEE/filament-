<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        // 1. 獲取表單數據
        $data = $this->form->getState();

        // 2. 嘗試認證
        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        // 3. 認證成功後獲取用戶（這時 user 一定存在）
        $user = Filament::auth()->user();

        // 4. 檢查電子郵件驗證狀態
        if (is_null($user->email_verified_at)) {
            // 用戶電子郵件未驗證，登出用戶
            Filament::auth()->logout();

            Notification::make()
                ->title('電子郵件未驗證')
                ->body('請先驗證您的電子郵件後再登入。')
                ->warning()
                ->send();

            return null;
        }

        // 5. 處理會話刷新（可選，但建議保留）
        session()->regenerate();

        // 6. 完成登入流程
        $this->form->fill();
        return app(LoginResponse::class);
    }
}
