<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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

    public function index()
    {
        // 總是先查詢可用事件，不管用戶是否登入
        $availableEvents = Event::where('status', 'available')
            ->where('starts_at', '>', now())
            ->with('doctor')
            ->get();

        // 返回視圖，讓前端LIFF處理用戶認證
        return view('line.appointment-livewire', [
            'events' => $availableEvents
        ]);
    }

    // 訂閱事件
    public function book(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'patient_name' => 'required|string',
            'patient_notes' => 'nullable|string',
            'line_user_id' => 'required|string'
        ]);

        // 嘗試通過姓名查找用戶
        $user = User::where('line_user_id', $validated['line_user_id'])->first();

        if (!$user) {
            $user = $this->checkOrCreateUser($validated['line_user_id'], $validated['patient_name']);
        }

        $event = $this->checkAndUpdateAppointment($validated['event_id'], $validated['patient_notes'], $validated['patient_name'], $user->id);

        return response()->json([
            'success' => true,
            'message' => '預約成功',
            'event' => $event->load('patient')
        ]);
    }

    // 檢查或創建用戶，並返回用戶資料
    public function checkOrCreateUser($line_user_id, $patient_name)
    {

        $user = User::where('line_user_id', $line_user_id)->first();

        if (!$user) {
            $user = User::create([
                'name' => $patient_name,
                'email' => ($line_user_id ?? Str::random(10)) . '@patient.local',
                'password' => bcrypt(Str::random(16)),
                'line_user_id' => $line_user_id,
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
        // 使用Livewire版本的視圖
        $lineUserId = $request->input('line_user_id');

        return view('line.appointment-history-livewire', [
            'lineUserId' => $lineUserId
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

        // 現在要用line_user_id去找用戶，然後再用這個用戶去找到對應的event事件
        $user = User::where('line_user_id', $validated['line_user_id'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '找不到用戶資料'
            ]);
        }

        // 獲取當前時間
        $now = now();

        // 獲取用戶的所有預約
        $appointments = Event::where('patient_id', $user->id)
            ->whereIn('status', ['booked', 'finished'])
            ->with('doctor')
            ->get();

        // 根據開始時間分類為即將到來和歷史預約
        $upcomingAppointments = $appointments->filter(function ($appointment) use ($now) {
            return $appointment->status === 'booked' && $appointment->starts_at >= $now;
        })->sortBy('starts_at')->values();

        $pastAppointments = $appointments->filter(function ($appointment) use ($now) {
            return $appointment->status === 'finished' ||
                ($appointment->status === 'booked' && $appointment->starts_at < $now);
        })->sortByDesc('starts_at')->values();

        return response()->json([
            'success' => true,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
        ]);
    }

    /**
     * 檢查事件是否可用，完成事件
     */
    public function checkAndUpdateAppointment($event_id, $patient_notes, $patient_name, $user_id)
    {
        $event = Event::findOrFail($event_id);
        if ($event->status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => '此時段已被預約，請選擇其他時段'
            ]);
        }

        // 更新事件狀態
        $event->update([
            'status' => 'booked',
            'patient_id' => $user_id,
            'patient_notes' => $patient_notes,
            'patient_name' => $patient_name,
        ]);

        return $event;
    }

    /**
     * 更新預約狀態為完成
     */
    public function updateAppointmentStatusToFinished($userId)
    {
        $now = now();
        // 自動將已過期但未標記為完成的預約更新為完成狀態
        $expiredAppointmentsCount = Event::where('patient_id', $userId)
            ->where('status', 'booked')
            ->where('starts_at', '<', $now)
            ->count();

        if ($expiredAppointmentsCount > 0) {
            Event::where('patient_id', $userId)
                ->where('status', 'booked')
                ->where('starts_at', '<', $now)
                ->update(['status' => 'finished']);
        }

        return $expiredAppointmentsCount;
    }

    /**
     * 獲取用戶所有預約
     */
    public function getUserAppointments($userId)
    {
        return Event::where('patient_id', $userId)
            ->whereIn('status', ['booked', 'finished'])
            ->with('doctor')
            ->get();
    }

    /**
     * 根據LINE用戶ID獲取所有預約並分類
     */
    public function getAppointmentsByLineUserId(Request $request)
    {
        $validated = $request->validate([
            'line_user_id' => 'required|string',
        ]);

        $user = User::where('line_user_id', $validated['line_user_id'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => '找不到用戶資料'
            ]);
        }

        $now = now();

        // 更新狀態
        $this->updateAppointmentStatusToFinished($user->id);

        // 獲取預約
        $appointments = $this->getUserAppointments($user->id);

        // 分類預約
        $upcomingAppointments = $appointments->filter(function ($appointment) use ($now) {
            return $appointment->status === 'booked' && $appointment->starts_at >= $now;
        })->sortBy('starts_at')->values();

        $pastAppointments = $appointments->filter(function ($appointment) {
            return $appointment->status === 'finished';
        })->sortByDesc('starts_at')->values();

        return response()->json([
            'success' => true,
            'appointments' => $appointments,
            'upcomingAppointments' => $upcomingAppointments,
            'pastAppointments' => $pastAppointments,
        ]);
    }
}
