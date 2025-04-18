<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// LINE相關路由
Route::prefix('line')->group(function () {
    // 預約相關
    Route::get('/appointment', 'App\Http\Controllers\LineAppointmentController@index')
        ->name('line.appointment');
    // 預約歷史頁面 - 直接返回視圖
    Route::get('appointment/history', 'App\Http\Controllers\LineAppointmentController@getHistoryPage')
        ->name('line.appointment.history');
  });
