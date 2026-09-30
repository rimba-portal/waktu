<?php

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = \Rimba\Time\Http\UI\Admin\Resources\Events\EventResource::class;

    protected static ?string $title = 'Calendar Events';

    protected ?string $subheading = 'Coordinate blocking operational items, holidays, and milestones.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
