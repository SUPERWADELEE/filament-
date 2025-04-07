<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class LineLoginController extends Controller
{
    public function redirectToLine(Request $request)
    {
        // 保存line_user_id到session中
        if ($request->has('line_user_id')) {
            session(['temp_line_user_id' => $request->input('line_user_id')]);
        }

        return Socialite::driver('line')->redirect();
    }

    public function handleLineCallback(Request $request)
    {
        try {
            // 從LINE OAuth獲取用戶數據
            $lineUser = Socialite::driver('line')->user();

            // 獲取LINE用戶ID
            $lineUserId = $lineUser->getId();
            $displayName = $lineUser->getName() ?? '未命名用戶';

            // 檢查是否已有對應的用戶
            $user = User::where('line_user_id', $lineUserId)->first();

            // 如果沒有，則創建新用戶
            if (!$user) {
                // 首先檢查是否有臨時用戶關聯
                $tempLineUserId = session('temp_line_user_id');

                // 檢查現有用戶是否已存在email
                $existingUser = User::where('email', $lineUser->getEmail())->first();

                if ($existingUser) {
                    // 更新現有用戶的LINE ID
                    $existingUser->line_user_id = $lineUserId;
                    $existingUser->save();
                    $user = $existingUser;
                } else {
                    // 創建一個新用戶
                    $user = User::create([
                        'name' => $displayName,
                        'email' => $lineUser->getEmail() ?? $lineUserId . '@line.user',
                        'password' => bcrypt(Str::random(16)),
                        'line_user_id' => $lineUserId,
                        'role' => 'patient',
                    ]);
                }
            }

            // 登入用戶
            Auth::login($user);

            // 重定向回預約頁面並帶上用戶ID
            return redirect()->route('line.appointment', [
                'line_user_id' => $lineUserId
            ]);
        } catch (\Exception $e) {
            return redirect()->route('line.appointment')
                ->with('error', 'LINE登入失敗: ' . $e->getMessage());
        }
    }
}
