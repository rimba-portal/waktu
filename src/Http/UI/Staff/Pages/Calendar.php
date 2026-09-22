<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Staff\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Rimba\Time\Services\CalendarEventService;
use Rimba\Time\Services\ShiftGeneratorService;
use UnitEnum;

final class Calendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Todo';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'bites::calendar';

    public array $events = [];

    public ?string $shiftRole = null;

    public function mount(CalendarEventService $events, ShiftGeneratorService $generator): void
    {
        $user = auth()->user();
        $this->shiftRole = $user ? $generator->roleFor($user) : null;
        if (! $user) {
            return;
        }

        $tz = (string) config('bites.time.timezone');
        $from = CarbonImmutable::now($tz)->subDays((int) config('bites.time.calendar_past_days', 45));
        $to = CarbonImmutable::now($tz)->addDays((int) config('bites.time.calendar_future_days', 120));
        $this->events = $events->forUser($user, $from, $to);
    }
}
