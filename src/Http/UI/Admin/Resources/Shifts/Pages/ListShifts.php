<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\ShiftResource;

class ListShifts extends ListRecords
{
    protected static string $resource = ShiftResource::class;

    protected static ?string $title = 'Shift Rosters';

    protected ?string $subheading = 'Organize daily working schedules and coverage logs for teams.';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
