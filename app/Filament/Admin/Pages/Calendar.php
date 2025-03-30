<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\CalendarWidget;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.admin.pages.calendar';

    protected static ?string $navigationLabel = '看診預約表';

    protected static ?string $title = '看診預約表';

    // 加載日曆小工具
    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}
