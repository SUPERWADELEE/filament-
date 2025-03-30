<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Widgets\CalendarWidget;
use Illuminate\Support\Facades\Gate;

class PatientAppointment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'filament.pages.patient-appointment';
    protected static ?string $navigationLabel = '預約門診';
    protected static ?string $title = '預約門診';

    public function mount(): void
    {
        // 確保只有病患可以訪問這個頁面
        Gate::authorize('isPatient');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CalendarWidget::class,
        ];
    }
}