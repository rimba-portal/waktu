<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Staff\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Rimba\Time\Services\CalendarEventService;
use UnitEnum;

final class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-s-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Todo';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'bites::calendar';

    public array $events = [];

    public function mount(CalendarEventService $service): void
    {
        $team = auth()->user()?->staff?->shiftCode;
        $this->events = $service->fullCalendarEvents($team);
    }
}
