<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages;

use Filament\Resources\Pages\CreateRecord;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\ShiftResource;

class CreateShift extends CreateRecord
{
    protected static string $resource = ShiftResource::class;
}
