<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Pages;

use Rimba\Time\Http\UI\Admin\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
