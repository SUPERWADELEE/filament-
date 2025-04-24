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
    protected $lastDateSelectTime = null;
    // 設定為全寬顯示
    protected static bool $isLazy = false;

    // 設定為佔據整個頁面
    protected int | string | array $columnSpan = 'full';

    // 完整型別宣告
    public Model | string | null $model = Event::class;

    // 事件顏色常量
    private const COLOR_BOOKED = '#FF0000';   // 紅色 - 已被他人預約
    private const COLOR_AVAILABLE = '#4CAF50';      // 綠色 - 可預約時段
    private const COLOR_FINISHED = '#000000';      // 黑色 - 已結束
    // 預約類型常量
    private const APPOINTMENT_TYPE_GENERAL = '一般診療';
    private const APPOINTMENT_TYPE_SPECIALIST = '專科診療';
    private const APPOINTMENT_TYPE_EMERGENCY = '緊急診療';

    // 狀態常量
    private const STATUS_BOOKED = 'booked';
    private const STATUS_AVAILABLE = 'available';
    private const STATUS_FINISHED = 'finished';
    // 標籤常量
    private const LABEL_DOCTOR = '醫生';
    private const LABEL_TREATMENT = '診療名稱';
    private const LABEL_START_TIME = '開始時間';
    private const LABEL_END_TIME = '結束時間';
    private const LABEL_APPOINTMENT_TIME = '預約時間';
    private const LABEL_APPOINTMENT_TYPE = '預約類型';
    private const LABEL_LOCATION = '診間';
    private const LABEL_PATIENT_NOTES = '症狀描述';
    private const LABEL_PATIENT = '預約病患';

    // 消息常量
    private const MSG_USE_CREATE_BUTTON = '請使用右上角的新增按鈕來創建預約時段';
    private const MSG_TIME_CONFLICT = '此時段已有其他預約，請選擇其他時間';
    private const MSG_START_BEFORE_END = '開始時間必須早於結束時間';
    private const MSG_START_DAY_AFTER = '開始時間必須至少比現在晚';

    // 其他常量
    private const DEBOUNCE_TIME_MS = 100;  // 防抖動時間間隔（毫秒）
    private const NAME_REGEX = '/^[a-zA-Z0-9\s\p{Han}]+$/u';  // 名稱正則表達式

    // 事件獲取與顯示相關
    /**
     * 根據用戶角色獲取日歷事件
     */
    public function fetchEvents(array $fetchInfo): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // 獲取指定時間範圍內的事件
        $events = $this->getEventsInRange($fetchInfo['start'], $fetchInfo['end']);

        // 確保有結果才進行映射
        if ($events->isEmpty()) {
            return [];
        }

        // 將事件轉換為日曆需要的格式
        return $events->map(function (Event $event) use ($user) {
            return [
                'id' => $event->id,
                'title' => $this->formatEventTitle($event, $user),
                'start' => $event->starts_at,
                'end' => $event->ends_at,
                'backgroundColor' => $this->getEventColor($event, $user),
            ];
        })->all();
    }

    /**
     * 獲取指定時間範圍內的事件
     */
    private function getEventsInRange(string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return Event::query()
            ->where('starts_at', '>=', $startDate)
            ->where('ends_at', '<=', $endDate)
            ->get();
    }

    /**
     * 根據事件狀態和用戶關係格式化事件標題
     */
    private function formatEventTitle(Event $event, $user): string
    {
        if ($event->status === Event::STATUS_BOOKED) {
            // if ($event->patient_id === $user->id) {
            //     return "我的預約：{$event->title}";
            // }

            $patientName = $event->patient ? $event->patient->name : '未知病患';
            return "已被預約：{$patientName} - 診療: {$event->title}";
        }
        if ($event->status === Event::STATUS_FINISHED) {
            return "已結束：{$event->title}";
        }

        if ($event->status === Event::STATUS_AVAILABLE) {
            $doctorName = $event->doctor ? $event->doctor->name : '未知醫生';
            return "可預約, 醫生: {$doctorName} - 診療: {$event->title}";
        }

        return $event->title;
    }

    /**
     * 獲取事件顏色
     */
    private function getEventColor(Event $event): string
    {
        if ($event->status === Event::STATUS_BOOKED) {
            return self::COLOR_BOOKED;
        }

        if ($event->status === Event::STATUS_FINISHED) {
            return self::COLOR_FINISHED;
        }

        if ($event->status === Event::STATUS_AVAILABLE) {
            return self::COLOR_AVAILABLE;
        }

        return self::COLOR_AVAILABLE; // 默認顏色
    }

    // 表單結構與字段定義

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
            return $this->getDoctorFormSchema($user);
        }

        if ($user->role === Event::ROLE['PATIENT']) {
            return $this->getPatientFormSchema($user);
        }

        return [];
    }

    /**
     * 醫生角色的表單定義
     */
    private function getDoctorFormSchema($user): array
    {
        return [
            $this->getTitleField(),
            $this->getDoctorNameField(),
            $this->getDateTimeFields(),
            $this->getAppointmentTypeField(),
            $this->getLocationField(),
            $this->getPatientNotesField(),
            Forms\Components\Hidden::make('doctor_id')->default($user->id),
            Forms\Components\Hidden::make('status')->default(self::STATUS_AVAILABLE),
        ];
    }

    /**
     * 病患角色的表單定義
     */
    private function getPatientFormSchema($user): array
    {
        return [
            $this->getTitlePlaceholder(),
            $this->getDoctorNameField(),
            $this->getAppointmentTimePlaceholder(),
            $this->getLocationPlaceholder(),
            $this->getPatientNotesField(),
            Forms\Components\Hidden::make('status')->default(self::STATUS_BOOKED),
            Forms\Components\Placeholder::make('patient_name')
                ->label(self::LABEL_PATIENT)
                ->content(fn($record) => $record?->patient?->name),
            Forms\Components\Hidden::make('patient_id')->default($user->id),
        ];
    }

    /**
     * 診療名稱輸入欄位
     */
    private function getTitleField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('title')
            ->label(self::LABEL_TREATMENT)
            ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED)
            ->maxLength(255)
            ->regex(self::NAME_REGEX)
            ->validationMessages([
                'regex' => '診療名稱只能包含中英文、數字和空格',
            ])
            ->required();
    }

    /**
     * 診療名稱顯示欄位
     */
    private function getTitlePlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('title')
            ->label(self::LABEL_TREATMENT)
            ->content(function ($record) {
                return $record ? $record->title : '無記錄';
            });
    }

    /**
     * 醫生名稱顯示欄位
     */
    private function getDoctorNameField(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('doctor_name')
            ->label(self::LABEL_DOCTOR)
            ->visible(fn($record) => $record && $record->doctor)
            ->content(fn($record) => $record->doctor->name);
    }

    /**
     * 日期時間選擇欄位
     */
    private function getDateTimeFields(): Forms\Components\Grid
    {
        return Forms\Components\Grid::make()
            ->schema([
                Forms\Components\DateTimePicker::make('starts_at')
                    ->label(self::LABEL_START_TIME)
                    ->required()
                    ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED),

                Forms\Components\DateTimePicker::make('ends_at')
                    ->label(self::LABEL_END_TIME)
                    ->required()
                    ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED)

            ]);
    }

    /**
     * 預約時間顯示欄位
     */
    private function getAppointmentTimePlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('appointment_time')
            ->label(self::LABEL_APPOINTMENT_TIME)
            ->content(function ($record) {
                if (!$record) {
                    return '未指定時間';
                }
                return $record->starts_at->format('Y-m-d H:i') . ' - ' .
                    $record->ends_at->format('H:i');
            });
    }

    /**
     * 預約類型選擇欄位
     */
    private function getAppointmentTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('appointment_type')
            ->label(self::LABEL_APPOINTMENT_TYPE)
            ->options([
                'general' => self::APPOINTMENT_TYPE_GENERAL,
                'specialist' => self::APPOINTMENT_TYPE_SPECIALIST,
                'emergency' => self::APPOINTMENT_TYPE_EMERGENCY,
            ])
            ->default(fn($record) => $record?->appointment_type)
            ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED)
            ->required();
    }

    /**
     * 診間輸入欄位
     */
    private function getLocationField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('location')
            ->label(self::LABEL_LOCATION)
            ->default(fn($record) => $record?->location)
            ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED)
            ->required();
    }

    /**
     * 診間顯示欄位
     */
    private function getLocationPlaceholder(): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('location')
            ->label(self::LABEL_LOCATION)
            ->content(fn($record) => $record ? $record->location : '未指定診間');
    }

    /**
     * 病患備註輸入欄位
     */
    private function getPatientNotesField(): Forms\Components\Textarea
    {
        return Forms\Components\Textarea::make('patient_notes')
            ->label(self::LABEL_PATIENT_NOTES)
            ->visible(fn($record) => $record !== null)
            ->required()
            ->disabled(fn($record) => $record?->status === self::STATUS_BOOKED);
    }

    // 交互動作與按鈕
    /**
     * 自定義頁面頭部創建按鈕   
     */
    protected function headerActions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // 如果用戶是病患，則不顯示任何操作按鈕
        if ($user->role === Event::ROLE['PATIENT']) {
            return [];
        }

        // 醫生角色的創建按鈕
        return [
            \Saade\FilamentFullCalendar\Actions\CreateAction::make()
                ->label('新增')
                ->icon('heroicon-o-plus')
                ->color('success')
                ->using(fn(array $data, $action) => $this->createAppointmentSlot($data, $action, $user))
        ];
    }

    /**
     * 完全覆蓋 onDateSelect 方法，使其不執行任何操作
     */
    public function onDateSelect($date, $endDate = null, $allDay = false, $view = [], $resource = null): void
    {
        // 這裡有發現他前端會有連續觸發，所以需要debounce
        $now = now();
        if ($this->lastDateSelectTime && $now->diffInMilliseconds($this->lastDateSelectTime) < self::DEBOUNCE_TIME_MS) {
            return;
        }
        $this->lastDateSelectTime = $now;
        $user = Auth::user();
        if ($user->role === Event::ROLE['DOCTOR']) {
            $this->sendSimpleNotification('提示', self::MSG_USE_CREATE_BUTTON);
        }
        // 如果用戶是病患，則不顯示任何操作按鈕
        return;
    }

    /**
     * 自定義刪除及編輯按鈕
     */
    protected function modalActions(): array
    {
        $user = Auth::user();
        if (!$user) {
            return [];
        }

        // 根據用戶角色決定顯示哪些操作按鈕
        if ($user->role === Event::ROLE['PATIENT']) {
            return $this->getPatientModalActions($user);
        }

        if ($user->role === Event::ROLE['DOCTOR']) {
            return $this->getDoctorModalActions($user);
        }

        // 其他情況使用默認行為
        return parent::modalActions();
    }

    /**
     * 獲取病患角色的模態操作按鈕
     */
    private function getPatientModalActions($user): array
    {
        return [
            $this->createEditAction($user, function (array $data, $record) use ($user): bool {
                // 確保預設值
                $data['status'] = self::STATUS_BOOKED;
                $data['patient_id'] = $user->id;

                // 更新記錄
                return $record->update([
                    'patient_notes' => $data['patient_notes'],
                    'status' => $data['status'],
                    'patient_id' => $data['patient_id'],
                ]);
            }),
            $this->createDeleteAction($user),
        ];
    }

    /**
     * 獲取醫生角色的模態操作按鈕
     */
    private function getDoctorModalActions($user): array
    {
        return [
            $this->createEditAction($user),
            $this->createDeleteAction($user),
        ];
    }

    /**
     * 創建編輯按鈕
     */
    private function createEditAction($user, ?callable $callback = null): \Saade\FilamentFullCalendar\Actions\EditAction
    {
        $action = \Saade\FilamentFullCalendar\Actions\EditAction::make()
            ->hidden(fn() => $this->isBookedOrOwnAppointment($user));

        if ($callback) {
            $action->using($callback);
        }

        return $action;
    }

    /**
     * 創建刪除按鈕
     */
    private function createDeleteAction($user): \Saade\FilamentFullCalendar\Actions\DeleteAction
    {
        return \Saade\FilamentFullCalendar\Actions\DeleteAction::make()
            ->hidden(fn() => $this->isBookedOrOwnAppointment($user))
            ->using(fn($record) => $record->delete());
    }

    /**
     * 檢查記錄是否已預約且不屬於當前用戶
     */
    private function isBookedOrOwnAppointment($user): bool
    {
        $isBooked = isset($this->record) && $this->record->status === self::STATUS_BOOKED || $this->record->status === self::STATUS_FINISHED;
        // $isOwnAppointment = isset($this->record) && $this->record->patient_id === $user->id;
        return $isBooked;
    }

    /**
     * 檢查時間衝突
     */
    private function hasTimeConflict($startsAt, $endsAt, $user): bool
    {
        return Event::query()
            ->where(function ($query) use ($startsAt, $endsAt) {
                $query->where(function ($q) use ($startsAt, $endsAt) {
                    $q->whereBetween('starts_at', [$startsAt, $endsAt])
                        ->orWhereBetween('ends_at', [$startsAt, $endsAt])
                        ->orWhere(function ($q2) use ($startsAt, $endsAt) {
                            $q2->where('starts_at', '<=', $startsAt)
                                ->where('ends_at', '>=', $endsAt);
                        });
                });
            })
            ->where('doctor_id', $user->id)
            ->exists();
    }

    // 業務邏輯與驗證
    /**
     * 創建預約時段並進行驗證
     */
    private function createAppointmentSlot(array $data, $action, $user): ?Event
    {
        // 執行所有驗證
        if (!$this->validateAppointmentTimes($data, $action, $user)) {
            return null;
        }

        // 創建記錄
        return Event::create($data);
    }

    /**
     * 驗證預約時間
     */
    private function validateAppointmentTimes(array $data, $action, $user): bool
    {
        $startsAt = $data['starts_at'];
        $endsAt = $data['ends_at'];

        // 檢查起始時間是否大於結束時間
        if ($startsAt >= $endsAt) {
            $this->sendErrorNotification('時間錯誤', self::MSG_START_BEFORE_END, $action);
            return false;
        }

        // 檢查起始時間是否至少比現在晚一天
        if ($startsAt < now()) {
            $this->sendErrorNotification('時間錯誤', self::MSG_START_DAY_AFTER, $action);
            return false;
        }

        // 檢查時間重疊
        if ($this->hasTimeConflict($startsAt, $endsAt, $user)) {
            $this->sendErrorNotification('時間衝突', self::MSG_TIME_CONFLICT, $action);
            return false;
        }

        return true;
    }

    // 通知處理
    /**
     * 發送錯誤通知並中止動作
     */
    private function sendErrorNotification(string $title, string $body, $action): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->send();
        $action->halt();
    }

    /**
     * 發送簡單通知，不中止動作
     */
    private function sendSimpleNotification(string $title, string $body): void
    {
        Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->send();
    }
}
