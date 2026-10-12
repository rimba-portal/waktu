<?php

declare(strict_types=1);

namespace Rimba\Time;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Rimba\Base\Services\BitesServiceProvider;
use Rimba\Floorplan\Http\UI\Staff\Pages\FloorPlan;
use Rimba\Time\Http\UI\Staff\Pages\Calendar;

class TimeServiceProvider extends BitesServiceProvider
{
    protected string $configFile = __DIR__.'/../config/bites.php';

    protected string $viewsPath = __DIR__.'/../resources/views';

    protected string $iconsPath = __DIR__.'/../resources/svg';

    protected function bootPackage(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->registerTimeTableHooks();
        $this->registerTimeShortcut();
    }

    protected function registerPackage(): void
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Floor Plan Table Hooks
    |--------------------------------------------------------------------------
    */
    protected function registerTimeTableHooks(): void
    {
        /*
         * Floor Plan filters: All | With Plans
         */
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_START,
            fn (): string => view('bites::time-tabs')->render(),
            scopes: [FloorPlan::class],
        );
        /*
         * Location management shortcut.
         *
         * Only shown when the user may create or edit locations. All record maintenance is delegated to the Team panel.
         */
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_AFTER,
            fn (): string => $this->renderManageTimeAction(),
            scopes: [FloorPlan::class],
        );
    }

    protected function renderManageTimeAction(): string
    {
        auth()->user();
        if (! Calendar::canManage()) {
            return '';
        }

        return Action::make('manageLocations')
            ->label('Edit')->icon('heroicon-o-pencil-square') // ->badge()
            // ->url(\Rimba\Floorplan\Http\UI\Team\Resources\Locations\LocationResource::getUrl('index', panel: 'team',))
            ->toHtml();
    }

    /*
       |--------------------------------------------------------------------------
       | Global Floor Plan Shortcut
       |--------------------------------------------------------------------------
       */
    protected function registerTimeShortcut(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            function (): string {
                // Check if the current panel is 'lobby'. If it is, return an empty string to render nothing.
                if (Filament::getCurrentPanel()?->getId() === 'lobby') {
                    return '';
                }

                /*
                 * Do not expose the shortcut when the user cannot
                * access the FloorPlan page.
                */
                if (! Calendar::canAccess()) {
                    return '';
                }

                return Action::make('Calendar')
                    ->label('Calendar')
                    ->iconButton()
                    ->badge()
                    ->icon('bites-calendar')
                    ->url(route('filament.staff.pages.calendar'))
                    ->toHtml();
            },
        );
    }
}
