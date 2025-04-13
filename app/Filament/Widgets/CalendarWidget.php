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

        // 這裏針對這裏針對每個預約事件，進行顏色和標題的設定
        return $events->map(function (Event $event) use ($user) {
            $backgroundColor = $this->getEventColor($event);
            $title = $event->title;

            // 只有可預約的時段才可選，TODO: 但目前還是可選= =
            $selectable = $event->status === Event::STATUS_AVAILABLE;

            // 根據角色自定義顯示
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
     * 根據用戶角色設定不同的表單,
     * TODO: 是否能從這裡去用資料庫過濾查詢資料
     */
    public function getFormSchema(): array
    {
        $user = Auth::user();
        // 醫生：創建可預約時段
        if ($user->role === Event::ROLE['DOCTOR']) {
            return [
                Forms\Components\TextInput::make('title')
                    ->label('診療名稱')
                    ->disabled(function ($record) {
                        return $record?->status === 'booked';
                    })
                    ->maxLength(2)
                    ->required(),

                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('開始時間')
                            ->required()
                            ->disabled(function ($record) {
                                return $record?->status === 'booked';
                            })
                            ->afterOrEqual(now()->addDay()->startOfMinute())
                            ->validationMessages([
                                'required' => '請選擇開始時間',
                                'after_or_equal' => '開始時間必須大於或等於明天',
                            ])
                            ->reactive(),

                        Forms\Components\DateTimePicker::make('ends_at')
                            ->label('結束時間')
                            ->required()
                            ->disabled(function ($record) {
                                return $record?->status === 'booked';
                            })
                            ->rules(['required', 'after:starts_at'])
                            ->afterOrEqual(fn (Forms\Get $get) => $get('starts_at') ?? now()->startOfMinute())
                            ->validationMessages([
                                'required' => '請選擇結束時間',
                                'after' => '結束時間必須大於開始時間',
                                'after_or_equal' => '結束時間不能小於開始時間',
                            ]),
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
        // 病患：預約時段
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

        // TODO: 在這裡檢查資料做法待觀察，這裏是從後端去擋，是否能從前端擋
        // 如果用戶是醫生，則顯示自定義創建按鈕
        return [
            \Saade\FilamentFullCalendar\Actions\CreateAction::make()
                ->using(function (array $data, $action) {
                    // 檢查起始時間是否大於結束時間
                    if ($data['starts_at'] >= $data['ends_at']) {
                        Notification::make()
                            ->title('時間錯誤')
                            ->body('開始時間必須早於結束時間')
                            ->danger()
                            ->send();
                        // 終止後續操作
                        $action->halt();
                    }

                    // 檢查起始時間是否至少比現在晚一天
                    if ($data['starts_at'] < now()->addDay()) {
                        Notification::make()
                            ->title('時間錯誤')
                            ->body('開始時間必須至少比現在晚一天')
                            ->danger()
                            ->send();
                        $action->halt();
                    }

                    $startsAt = $data['starts_at'];
                    $endsAt = $data['ends_at'];

                    // 檢查時間重疊
                    $query = Event::where(function ($query) use ($startsAt, $endsAt) {
                        $query->where(function ($q) use ($startsAt, $endsAt) {
                            // 新開始時間在其他預約範圍內
                            $q->where('starts_at', '<=', $startsAt)
                                ->where('ends_at', '>', $startsAt);
                        })->orWhere(function ($q) use ($startsAt, $endsAt) {
                            // 新結束時間在其他預約範圍內
                            $q->where('starts_at', '<', $endsAt)
                                ->where('ends_at', '>=', $endsAt);
                        })->orWhere(function ($q) use ($startsAt, $endsAt) {
                            // 新預約完全包含其他預約
                            $q->where('starts_at', '>=', $startsAt)
                                ->where('ends_at', '<=', $endsAt);
                        });
                    });

                    // 印出SQL查詢語句
                    $sql = $query->toSql();
                    $bindings = $query->getBindings();

                    // 替換綁定參數
                    foreach ($bindings as $binding) {
                        $value = is_string($binding) ? "'" . $binding . "'" : $binding;
                        $sql = preg_replace('/\?/', $value, $sql, 1);
                    }

                    // 使用 dd() 印出完整SQL (會中斷執行)
                    // dd($sql);

                    // 或使用 Log::info 記錄SQL (不會中斷執行)
                    \Illuminate\Support\Facades\Log::info('預約時間檢查SQL: ' . $sql);
                    if ($query->exists()) {
                        // 顯示錯誤通知
                        Notification::make()
                            ->title('時間衝突')
                            ->body('此時段已有其他預約，請選擇其他時間')
                            ->danger()
                            ->send();

                        // 使用 halt() 方法停止後續操作
                        $action->halt();
                    }

                    // 創建記錄
                    return Event::create($data);
                })
        ];
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
     * 編輯事件的表單邏輯，可以根據事件選擇是否要顯示編輯按鈕
     */
    protected function modalActions(): array
    {
        $user = Auth::user();
        if ($user->role === Event::ROLE['PATIENT']) {
            return [
                // 編輯按鈕  判斷是否為該用戶的預約，如果是已預約狀態則不顯示
                \Saade\FilamentFullCalendar\Actions\EditAction::make()
                    ->hidden(function () use ($user) {
                        $isBooked = isset($this->record) && $this->record->status === 'booked';
                        $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;
                        return $isBooked && !$isOwnAppointment;
                    })
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
                    }),
                // 刪除按鈕
                \Saade\FilamentFullCalendar\Actions\DeleteAction::make()
                    // 只有當是用戶自己的預約且不是已預約狀態時才啟用
                    ->hidden(function () use ($user) {
                        $isBooked = isset($this->record) && $this->record->status === 'booked';
                        $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;
                        return $isBooked && !$isOwnAppointment;
                    })
                    ->using(function ($record) {
                        // 刪除邏輯...
                        return $record->delete();
                    }),
            ];
        }

        if ($user->role === Event::ROLE['DOCTOR']) {
            return [
                \Saade\FilamentFullCalendar\Actions\EditAction::make()
                    ->hidden(function () use ($user) {
                        $isBooked = isset($this->record) && $this->record->status === 'booked';
                        $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;
                        return $isBooked && !$isOwnAppointment;
                    }),

                \Saade\FilamentFullCalendar\Actions\DeleteAction::make()
                    ->hidden(function () use ($user) {
                        $isBooked = isset($this->record) && $this->record->status === 'booked';
                        $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;
                        return $isBooked && !$isOwnAppointment;
                    })
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
