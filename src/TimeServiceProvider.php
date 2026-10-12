<?php

declare(strict_types=1);

namespace Rimba\Time;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Rimba\Base\Services\BitesServiceProvider;
use Rimba\Time\Http\UI\Staff\Pages\Calendar;

class TimeServiceProvider extends BitesServiceProvider
{
    protected string $configFile = __DIR__.'/../config/bites.php';

    protected string $viewsPath = __DIR__.'/../resources/views';

    protected string $iconsPath = __DIR__.'/../resources/svg';

    protected function bootPackage(): void
    {
        $this->loadMigrationsFrom(
            __DIR__.'/../database/migrations'
        );

        $this->registerCalendarTableHooks();
        $this->registerCalendarShortcut();
    }

    protected function registerPackage(): void
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Calendar Table Hooks
    |--------------------------------------------------------------------------
    */

    protected function registerCalendarTableHooks(): void
    {
        /*
         * Calendar filters / tabs.
         */
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_START,
            fn (): string => view('bites::time-tabs')->render(),
            scopes: [Calendar::class],
        );

        /*
         * Time-management shortcut.
         *
         * Staff view remains read-only.
         * Maintenance is delegated to the Team panel.
         */
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_AFTER,
            fn (): string => $this->renderManageTimeAction(),
            scopes: [Calendar::class],
        );
    }

    protected function renderManageTimeAction(): string
    {
        if (! Calendar::canManage()) {
            return '';
        }

        return Action::make('manageTime')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->url(
                route('filament.team.pages.manage-holidays')
            )
            ->toHtml();
    }

    /*
    |--------------------------------------------------------------------------
    | Global Calendar Shortcut
    |--------------------------------------------------------------------------
    */

    protected function registerCalendarShortcut(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            function (): string {
                if (Filament::getCurrentPanel()?->getId() === 'lobby') {
                    return '';
                }

                if (! Calendar::canAccess()) {
                    return '';
                }

                return Action::make('calendar')
                    ->label('Calendar')
                    ->iconButton()
                    ->icon('bites-calendar')
                    ->url(route('filament.staff.pages.calendar'))
                    ->toHtml();
            },
        );
    }
}
