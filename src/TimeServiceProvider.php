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
        $this->registerCalendarTableHooks();
        $this->registerCalendarShortcut();
    }

    protected function registerPackage(): void
    {
        //
    }

    protected function registerCalendarTableHooks(): void
    {
        FilamentView::registerRenderHook(
            TablesRenderHook::TOOLBAR_SEARCH_BEFORE,
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
            ->badge()
            ->url(route('filament.team.pages.manage-calendar'))
            ->toHtml();
    }

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
                    ->badge()
                    ->icon('bites-calendar')
                    ->url(route('filament.staff.pages.calendar'))
                    ->toHtml();
            },
        );
    }
}
