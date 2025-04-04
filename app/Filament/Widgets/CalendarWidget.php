<?php

namespace App\Filament\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Models\Event;
use Filament\Forms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;


class CalendarWidget extends FullCalendarWidget
{
    // 設定為全寬顯示
    protected static bool $isLazy = false;

    // 設定為佔據整個頁面
    protected int | string | array $columnSpan = 'full';

    // 完整型別宣告
    public Model | string | null $model = Event::class;

    /**
     * 根據用戶角色獲取事件，最外層日歷
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();

        // 如果沒有登入用戶，返回空陣列
        if (!$user) {
            return [];
        }

        $query = Event::query()
            ->where('starts_at', '>=', $fetchInfo['start'])
            ->where('ends_at', '<=', $fetchInfo['end']);

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

            // 自定義顯示和行為
            if ($user->role === Event::ROLE['DOCTOR'] && $event->status === Event::STATUS_BOOKED) {
                $title = "已被預約：" . ($event->patient ? $event->patient->name : '未知病患') . " - {$event->title}";
            } elseif ($user->role === Event::ROLE['PATIENT']) {
                if ($event->status === Event::STATUS_AVAILABLE) {
                    $title = "可預約：" . ($event->doctor ? $event->doctor->name : '未知醫生') . " - {$event->title}";
                } elseif ($event->status === Event::STATUS_BOOKED && $event->patient_id === $user->id) {
                    $title = "我的預約：" . $event->title;
                } elseif ($event->status === Event::STATUS_BOOKED) {
                    $backgroundColor = '#2196F3'; // 藍色：已預約
                    $title = "已被預約：" . $event->title;
                    if ($event->patient_id !== $user->id) {
                        $selectable = false;  // 明確設置為不可選
                    }
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
     * 預約表單
     * 根據用戶角色設定不同的表單
     */
    public function getFormSchema(): array
    {
        $user = Auth::user();
        // 醫生表單：創建可預約時段
        if ($user->role === Event::ROLE['DOCTOR']) {
            return [
                Forms\Components\TextInput::make('title')
                    ->label('診療名稱')
                    ->disabled(function ($record) {
                        return $record?->status === 'booked';
                    })
                    ->required(),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('開始時間')
                            ->required()
                            ->disabled(function ($record) {
                                return $record?->status === 'booked';
                            }),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('結束時間')
                            ->required()
                            ->disabled(function ($record) {
                                return $record?->status === 'booked';
                            }),
                    ]),

                Forms\Components\Select::make('appointment_type')
                    ->label('預約類型')
                    ->options([
                        'general' => '一般診療',
                        'specialist' => '專科診療',
                        'emergency' => '緊急診療',
                    ])
                    ->default(function ($record) {
                        return $record?->appointment_type;
                    })
                    ->disabled(function ($record) {
                        // 如果是編輯現有記錄，則禁用
                        return $record?->status === 'booked';
                    })
                    ->required(),

                Forms\Components\TextInput::make('location')
                    ->label('診間')
                    ->default(function ($record) {
                        return $record?->location;
                    })
                    ->disabled(function ($record) {
                        return $record?->status === 'booked';
                    })
                    ->required(),



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
                    ->required()
                    ->disabled(function ($record) {
                        return $record?->status === 'booked';
                    }),

                Forms\Components\Hidden::make('status')
                    ->default('booked'),
                // 假設有預約過了顯示預約病患
                Forms\Components\Placeholder::make('patient_name')
                    ->label('預約病患')
                    ->content(function ($record) {
                        return $record?->patient?->name;
                    }),

                // 隱藏欄位 - 直接使用當前用戶 ID 和預設狀態
                Forms\Components\Hidden::make('patient_id')
                    ->default($user->id),
            ];
        }
        return [];
    }
    /**
     * 自定義日曆配置
     */
    public function config(): array
    {
        return [
            'selectable' => false,
            'dateClick' => false,
            'initialView' => 'dayGridMonth',
            'headerToolbar' => [
                'left' => 'dayGridMonth,timeGridWeek,timeGridDay',
                'center' => 'title',
                'right' => 'prev,next today',
            ],

        ];
    }
    /**
     * 自定義頁面頭部按鈕
     */
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
        return parent::headerActions();  // 這會返回包含創建按鈕的數組
    }

    /**
     * TODO: 這裡我希望不讓任何人去點擊日期更擊，現在寫法還是會打update事件，但不做任何反應
     * 完全覆蓋 onDateSelect 方法，使其不執行任何操作
     */
    public function onDateSelect($date, $endDate = null, $allDay = false, $view = [], $resource = null): void
    {
        $user = Auth::user();
        if ($user->role === Event::ROLE['DOCTOR']) {
            Notification::make()
                ->title('提示')
                ->body('請使用右上角的新增按鈕來創建預約時段')
                ->info()
                ->send();
        }
        // 不執行任何操作，直接返回
        return;
        // 可選：顯示通知告知用戶

    }

    /**
     * 編輯事件的後端邏輯，這裡透過自訂義去確保寫進資料庫資料有預設值
     */
    protected function modalActions(): array
    {
        $user = Auth::user();
        // 判斷條件：是否為該用戶的預約
        $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;

        // 判斷條件：是否為已預約狀態
        $isBooked = isset($this->record) && $this->record->status === 'booked';
        if ($user->role === Event::ROLE['PATIENT']) {


            return [
                \Saade\FilamentFullCalendar\Actions\EditAction::make()
                    ->using(function (array $data, $record) use ($user): bool {
                        // 確保預設值
                        $data['status'] = 'booked';
                        $data['patient_id'] = $user->id;

                        // 更新記錄
                        return $record->update([
                            'patient_notes' => $data['patient_notes'],
                            'status' => $data['status'],
                            'patient_id' => $data['patient_id'],
                        ]);
                    })
                    ->hidden($isBooked && !$isOwnAppointment),
                \Saade\FilamentFullCalendar\Actions\DeleteAction::make()
                    // 只有當是用戶自己的預約且不是已預約狀態時才啟用
                    ->hidden($isBooked && !$isOwnAppointment)
                    ->using(function ($record) {
                        // 刪除邏輯...
                        return $record->delete();
                    }),
            ];
        }

        if ($user->role === Event::ROLE['DOCTOR']) {
            return [
                \Saade\FilamentFullCalendar\Actions\EditAction::make()
                    ->hidden($isBooked),

                \Saade\FilamentFullCalendar\Actions\DeleteAction::make()
                    ->hidden($isBooked && !$isOwnAppointment)
                    ->using(function ($record) {
                        // 刪除邏輯...
                        return $record->delete();
                    }),
            ];
        }
        // 其他情況使用默認行為
        return parent::modalActions();
    }
}
