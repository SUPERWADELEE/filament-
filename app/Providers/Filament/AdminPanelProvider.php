<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use App\Filament\Widgets\CalendarWidget;
use App\Filament\Pages\Auth\Register;
use App\Filament\Pages\Auth\Login;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Auth;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {;
        return $panel
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            // ->registration(Register::class)
            ->brandName('醫療預約系統')
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
            ->widgets([
                CalendarWidget::class,
            ])
            ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
                return $builder
                    ->item(
                        NavigationItem::make('用戶管理')
                            ->icon('heroicon-o-user-group')
                            ->url(route('filament.admin.resources.users.index'))
                            // 只有管理員可以看到
                            ->visible(fn() => Auth::user()->role === 'admin')
                    )
                    ->item(
                        NavigationItem::make('預約管理')
                            ->icon('heroicon-o-calendar')
                            ->url(route('filament.admin.pages.calendar'))
                            ->hidden(fn() => Auth::user()->role == 'admin')
                    );
            })
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
            ])
            ->default();
    }
}
