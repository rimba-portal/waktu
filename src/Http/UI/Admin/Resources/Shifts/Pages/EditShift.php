<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages;

use Rimba\Time\Http\UI\Admin\Resources\Shifts\ShiftResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditShift extends EditRecord
{
    protected static string $resource = ShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
