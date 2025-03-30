<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Widgets\CalendarWidget;
use App\Filament\Admin\Pages\Calendar;
use App\Filament\Resources\AppointmentResource;
use App\Filament\Resources\PatientResource;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // 確定用戶角色
        $userRole = auth()->check() ? auth()->user()->role : null;

        // 根據角色準備資源陣列
        $resources = [];
        if ($userRole === 'doctor') {
            $resources = [
                \App\Filament\Admin\Resources\EventResource::class,
                // 其他醫生可見的資源...
            ];
        } elseif ($userRole === 'patient') {
            $resources = [
                // 病患可見的資源...
            ];
        }

        // 準備頁面陣列
        $pages = [\Filament\Pages\Dashboard::class];
        if ($userRole === 'doctor') {
            $pages[] = \App\Filament\Admin\Pages\Calendar::class;
            // 或 \App\Filament\Pages\DoctorSchedule::class
        } elseif ($userRole === 'patient') {
            $pages[] = \App\Filament\Pages\PatientAppointment::class;
        }

        return $panel
            ->id('admin')
            ->path('admin')
            ->login()
            ->registration()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->authGuard('web')
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')

            ->plugin(
                FilamentFullCalendarPlugin::make()
                    ->selectable(true)
                    ->editable(true)
                    ->timezone('Asia/Taipei')
                    ->locale('zh-tw')
            )
            ->resources($resources)
            ->pages($pages)
            ->widgets([
                CalendarWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
