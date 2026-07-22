<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Pages;

use Rimba\Time\Http\UI\Admin\Resources\Events\EventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
