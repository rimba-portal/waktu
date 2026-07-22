<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Pages;

use Rimba\Time\Http\UI\Admin\Resources\Events\EventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
