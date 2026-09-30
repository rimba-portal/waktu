<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Rimba\Time\Http\UI\Admin\Resources\Events\EventResource;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected static ?string $title = 'Calendar Events';

    protected ?string $subheading = 'Coordinate blocking operational items, holidays, and milestones.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
