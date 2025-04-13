<?php

use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
Route::get('/', function () {
    return view('welcome');
});
Route::get('/check-auth', function () {
    if (Auth::check()) {
        return [
            'login' => true,
            'user' => Auth::user(),
        ];
    } else {
        return [
            'login' => false,
        ];
    }
});


// 添加電子郵件路由
Route::get('/email/verify/{id}/{token}', function ($id, $token) {
    $user = User::findOrFail($id);

    if ($user->verification_token !== $token) {
        abort(403, '驗證連結無效');
    }

    // 使用 Eloquent 模型更新
    $user->email_verified_at = now();
    $user->verification_token = null;
    $user->save();

    // 添加一個會話標記，指示電子郵件已驗證
    session()->flash('email_verified', true);

    return redirect()->route('filament.admin.auth.login')
        ->with('success', '電子郵件已驗證，請登入您的帳戶');
})->name('verification.verify')->middleware('signed');
// LINE相關路由
Route::prefix('line')->group(function () {
    // 預約相關
    Route::get('/appointment', 'App\Http\Controllers\LineAppointmentController@index')
        ->name('line.appointment');
    Route::post('/appointment/book', 'App\Http\Controllers\LineAppointmentController@book')
        ->name('line.appointment.book');
    Route::post('/check/user', 'App\Http\Controllers\LineAppointmentController@checkOrCreateUser')
        ->name('line.check.user');
    // 預約歷史頁面 - 直接返回視圖
    Route::get('appointment/history', 'App\Http\Controllers\LineAppointmentController@getHistoryPage')
        ->name('line.appointment.history');
    Route::post('/appointment/history/fetch', 'App\Http\Controllers\LineAppointmentController@fetchHistory')
        ->name('line.appointment.history.fetch');

        
    // 登入相關
    Route::get('/login', 'App\Http\Controllers\LineLoginController@redirectToLine')
        ->name('line.login');
    Route::get('/callback', 'App\Http\Controllers\LineLoginController@handleLineCallback')
        ->name('line.callback');
});
