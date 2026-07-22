<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages;

use Rimba\Time\Http\UI\Admin\Resources\Shifts\ShiftResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewShift extends ViewRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
