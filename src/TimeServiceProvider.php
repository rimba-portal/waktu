<?php

declare(strict_types=1);

namespace Rimba\Time;

use Filament\Actions\Action;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Rimba\Base\BitesServiceProvider;

class TimeServiceProvider extends BitesServiceProvider
{
    protected string $configFile = __DIR__.'/../config/bites.php';

    protected string $viewsPath = __DIR__.'/../resources/views';

    protected string $iconsPath = __DIR__.'/../resources/svg';

    protected function bootPackage(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn (): string => Action::make('Calendar')
                ->label('Calendar')
                ->iconButton()
                ->badge()
                ->icon('bites-calendar')
                ->url(route('filament.staff.pages.calendar'))
                ->toHtml(),
        );

    }

    protected function registerPackage(): void
    {
        //
    }
}
