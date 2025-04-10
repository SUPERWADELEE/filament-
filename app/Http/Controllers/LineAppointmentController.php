<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LineAppointmentController extends Controller
{
    public function __construct()
    {
        // 強制所有資源使用 HTTPS (修復 ngrok 問題)
        if (str_contains(request()->getHttpHost(), 'ngrok')) {
            URL::forceScheme('https');
        }
    }

    public function index(Request $request)
    {
        // 總是先查詢可用事件，不管用戶是否登入
        $availableEvents = Event::where('status', 'available')
            ->where('starts_at', '>', now())
            ->with('doctor')
            ->get();

        // 返回視圖，讓前端LIFF處理用戶認證
        return view('line.appointment', [
            'events' => $availableEvents
        ]);
    }

    public function book(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'patient_name' => 'required|string',
            'patient_notes' => 'nullable|string',
            'line_user_id' => 'nullable|string' // 改為可選
        ]);

        // 檢查是否有用戶登入
        if (!Auth::check()) {
            // 嘗試通過姓名查找用戶
            $user = User::where('line_user_id', $validated['line_user_id'])->first();

            // 如果找不到用戶但有LINE ID，嘗試通過LINE ID查找
            if (!$user && !empty($validated['line_user_id'])) {
                $user = User::where('line_user_id', $validated['line_user_id'])->first();
            }

            // 如果還是找不到用戶，創建一個新用戶
            if (!$user) {
                $user = User::create([
                    'name' => $validated['patient_name'],
                    'email' => ($validated['line_user_id'] ?? Str::random(10)) . '@patient.local',
                    'password' => bcrypt(Str::random(16)),
                    'line_user_id' => $validated['line_user_id'] ?? null,
                    'role' => 'patient',
                ]);
            }

            // 登入找到或創建的用戶
            Auth::login($user);
        } else {
            $user = Auth::user();
        }

        // 檢查事件是否可用
        $event = Event::findOrFail($validated['event_id']);
        if ($event->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => '此時段已被預約，請選擇其他時段'
            ]);
        }

        // 更新事件狀態
        $event->update([
            'status' => 'booked',
            'patient_id' => $user->id,
            'patient_notes' => $validated['patient_notes'],
            'patient_name' => $validated['patient_name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => '預約成功',
            'event' => $event->load('patient')
        ]);
    }

    public function checkOrCreateUser(Request $request)
    {
        $validated = $request->validate([
            'line_user_id' => 'required|string',
            'display_name' => 'required|string'
        ]);

        $user = User::where('line_user_id', $validated['line_user_id'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $validated['display_name'],
                'email' => ($validated['line_user_id'] ?? Str::random(10)) . '@patient.local',
                'password' => bcrypt(Str::random(16)),
                'line_user_id' => $validated['line_user_id'],
                'role' => 'patient',
            ]);
        }

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }
    /**
     * 顯示預約歷史頁面
     */
    public function getHistoryPage(Request $request)
    {
        // 調試日誌
        Log::info('進入 getHistoryPage 方法');
        Log::info('用戶登入狀態: ' . (Auth::check() ? '已登入' : '未登入'));

        // 檢查用戶是否已登入
        if (!Auth::check()) {
            Log::info('用戶未登入，顯示登入提示');
            // 未登入，可以重定向到登入頁面或顯示提示訊息
            return view('line.appointmentHistory', [
                'events' => [],
                'needLogin' => true
            ]);
        }

        $user = Auth::user();
        Log::info('用戶已登入, ID: ' . $user->id . ', 名稱: ' . $user->name);

        // 返回當前用戶已預約過的事件
        $bookedEvents = Event::where('status', 'booked')
            ->where('patient_id', $user->id)
            ->with('doctor')
            ->get();

        Log::info('找到 ' . $bookedEvents->count() . ' 個預約');

        return view('line.appointmentHistory', [
            'events' => $bookedEvents,
            'needLogin' => false
        ]);
    }

    /**
     * 獲取用戶預約歷史
     */
    public function fetchHistory(Request $request)
    {
        $validated = $request->validate([
            'line_user_id' => 'required|string',
        ]);

        // 查找用戶
        $user = User::where('line_user_id', $validated['line_user_id'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '找不到用戶資料'
            ]);
        }

        // 獲取用戶的預約歷史
        $appointments = Event::where('patient_id', $user->id)
            ->where('status', 'booked')
            ->with('doctor')
            ->orderBy('starts_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'appointments' => $appointments
        ]);
    }
}
