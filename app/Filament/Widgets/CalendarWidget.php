<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Event;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;


class CalendarWidget extends FullCalendarWidget
{
    // 設定為全寬顯示
    protected static bool $isLazy = false;

    // 設定為佔據整個頁面
    protected int | string | array $columnSpan = 'full';

    // 完整型別宣告
    public Model | string | null $model = Event::class;

    public function mount(): void
    {
        // 或
        Log::info('Widget mounted');
    }

    /**
     * 根據用戶角色獲取事件
     */
    public function fetchEvents(array $fetchInfo): array
    {
        Log::info('fetchEvents 被調用', $fetchInfo);
        // dd('fetchEvents 被調用');
        $user = Auth::user();

        // 如果沒有登入用戶，返回空陣列
        if (!$user) {
            return [];
        }

        $query = Event::query()
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end']);

        // 如果是醫生，只顯示自己創建的時段
        // if ($user->role === 'doctor') {
        //     $query->where('doctor_id', $user->id);
        // }

        // 如果是病患，顯示所有事件但不同狀態用不同顏色表示
        // if ($user->role === 'patient') {
        //     // 不對查詢添加條件，顯示所有時段
        //     // 或者，只過濾出特定時段，例如：
        //     $query->where(function ($q) use ($user) {
        //         $q->where('status', 'available')  // 可預約時段
        //           ->orWhere('patient_id', $user->id)  // 自己的預約
        //           ->orWhere('status', 'booked');  // 已被他人預約的時段
        //     });
        // }

        $events = $query->get();

        // 確保有結果才進行映射
        if ($events->isEmpty()) {
            return [];
        }

        return $events->map(function (Event $event) use ($user) {
            $backgroundColor = $this->getEventColor($event);
            $title = $event->title;

            // 只有可預約的時段才可選
            $selectable = $event->status === Event::STATUS_AVAILABLE;

            $selectable = true;
            // 自定義顯示和行為
            if ($user->role === Event::ROLE['DOCTOR'] && $event->status === Event::STATUS_BOOKED) {
                $title = "預約：" . ($event->patient ? $event->patient->name : '未知病患') . " - {$event->title}";
            } elseif ($user->role === Event::ROLE['PATIENT']) {
                if ($event->status === Event::STATUS_AVAILABLE) {
                    $title = "可預約：" . ($event->doctor ? $event->doctor->name : '未知醫生') . " - {$event->title}";
                } elseif ($event->status === Event::STATUS_BOOKED && $event->patient_id === $user->id) {
                    $title = "我的預約：" . $event->title;
                } elseif ($event->status === Event::STATUS_BOOKED) {
                    $title = "已被預約：" . $event->title;
                    // $selectable = false;  // 明確設置為不可選
                }
            }

            return [
                'id' => $event->id,
                'title' => $title,
                'start' => $event->starts_at,
                'end' => $event->ends_at,
                'backgroundColor' => $backgroundColor,
                'extendedProps' => [
                    'status' => $event->status,
                    'doctor' => $event->doctor ? $event->doctor->name : null,
                    'patient' => $event->patient ? $event->patient->name : null,
                    'patient_id' => $event->patient_id,
                    'type' => $event->appointment_type ?? '',
                    'location' => $event->location ?? '',
                    'selectable' => $selectable,
                ],
            ];
        })->all();
    }

    /**
     * 根據事件狀態獲取顏色
     */
    private function getEventColor(Event $event): string
    {
        if (!isset($event->status)) {
            return '#9E9E9E'; // 默認灰色
        }

        if ($event->status === 'available') {
            return '#4CAF50'; // 綠色：可預約
        } elseif ($event->status === 'booked') {
            return '#2196F3'; // 藍色：已預約
        } elseif ($event->status === 'completed') {
            return '#9E9E9E'; // 灰色：已完成
        } elseif ($event->status === 'canceled') {
            return '#F44336'; // 紅色：已取消
        }

        return '#9E9E9E'; // 默認灰色
    }

    /**
     * 根據用戶角色設定不同的表單
     */
    public function getFormSchema(): array
    {
        $user = Auth::user();

        // 醫生表單：創建可預約時段
        if ($user->role === 'doctor') {
            return [
                Forms\Components\TextInput::make('title')
                    ->label('診療名稱')
                    ->required(),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('開始時間')
                            ->required(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('結束時間')
                            ->required(),
                    ]),

                Forms\Components\Select::make('appointment_type')
                    ->label('預約類型')
                    ->options([
                        'general' => '一般診療',
                        'specialist' => '專科診療',
                        'emergency' => '緊急診療',
                    ])
                    ->required(),

                Forms\Components\TextInput::make('location')
                    ->label('診間')
                    ->required(),

                // Forms\Components\Textarea::make('description')
                //     ->label('說明'),

                Forms\Components\Hidden::make('doctor_id')
                    ->default($user->id),

                Forms\Components\Hidden::make('status')
                    ->default('available'),
            ];
        }

        // 病患表單：預約時段
        if ($user->role === Event::ROLE['PATIENT']) {
            return [
                // 使用條件顯示，確保有記錄時才訪問記錄屬性
                Forms\Components\Placeholder::make('title')
                    ->label('診療名稱')
                    ->content(function ($record) {
                        // 檢查 $record 是否存在
                        if (!$record) {
                            return '無記錄';
                        }
                        return $record->title;
                    }),

                Forms\Components\Placeholder::make('doctor_name')
                    ->label('醫生')
                    ->content(function ($record) {
                        if (!$record || !$record->doctor) {
                            return '無醫生資訊';
                        }
                        return $record->doctor->name;
                    }),

                Forms\Components\Placeholder::make('appointment_time')
                    ->label('預約時間')
                    ->content(function ($record) {
                        if (!$record) {
                            return '未指定時間';
                        }
                        return $record->starts_at->format('Y-m-d H:i') . ' - ' .
                            $record->ends_at->format('H:i');
                    }),

                Forms\Components\Placeholder::make('location')
                    ->label('診間')
                    ->content(function ($record) {
                        if (!$record) {
                            return '未指定診間';
                        }
                        return $record->location;
                    }),

                Forms\Components\Textarea::make('patient_notes')
                    ->label('症狀描述')
                    ->visible(fn($record) => $record !== null)
                    ->required(),
                Forms\Components\Placeholder::make('status')
                ->label('預約狀態')
                ->content(function ($record) {
                    if (!$record) {
                        return '未指定狀態';
                    }
                    return $record->status;
                })
                ->default('booked'),

                Forms\Components\Hidden::make('patient_id')
                    ->default($user->id),


            ];
        }

        return [];
    }

    /**
     * 處理更新事件
     */
    protected function handleEventClick(array $data): void
    {
        Log::info('handleEventClick 被調用', $data);
        $user = Auth::user();
        $event = Event::find($data['id']);

        if (!$event) {
            return;
        }

        if ($user->role === Event::ROLE['PATIENT']) {
            $event->update([
                'patient_id' => $user->id,
                'status' => 'booked',
                'patient_notes' => $data['patient_notes'] ?? null,
            ]);

            Notification::make()
                ->title('預約成功')
                ->success()
                ->send();
        }
    }

    /**
     * 自定義日曆配置
     */
    public function config(): array
    {
        $user = Auth::user();
        return [
             // ... 其他設定
        'eventClick' => 'function(info) { console.log("Event clicked:", info); }',
            // 'firstDay' => 1,
            // 'headerToolbar' => [
            //     'left' => 'dayGridMonth,timeGridWeek,timeGridDay',
            //     'center' => 'title',
            //     'right' => 'prev,next today',
            // ],
            // 'slotDuration' => '00:15:00', // 15分鐘一個時段
            // 'slotMinTime' => '08:00:00',  // 診所開始時間
            // 'slotMaxTime' => '18:00:00',  // 診所結束時間
            // 'height' => '700px',
            // 'selectable' => $user->role === 'doctor', // 只有醫生可以選擇時段創建預約
            // 'editable' => $user->role === 'doctor',   // 只有醫生可以拖動和調整時段

        ];
    }

    protected function headerActions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // 如果用戶是病患，則不顯示任何操作按鈕
        if ($user && $user->role === Event::ROLE['PATIENT']) {
            return [];
        }

        // 如果用戶是醫生，則顯示默認的創建按鈕
        return parent::headerActions();
    }
}
