<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\CalendarWidget;
use Illuminate\Support\Facades\Gate;

class DoctorSchedule extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'filament.pages.doctor-schedule';
    protected static ?string $navigationLabel = '排班管理';
    protected static ?string $title = '排班管理';

    public function mount(): void
    {
        // 確保只有醫生可以訪問這個頁面
        Gate::authorize('isDoctor');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}