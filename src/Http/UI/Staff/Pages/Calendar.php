<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Staff\Pages;

use BackedEnum;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Rimba\Time\Enums\EventType;
use Rimba\Time\Services\CalendarEventService;
use Rimba\Time\Services\ShiftGeneratorService;
use Rimba\Time\Services\TimeJsonRepository;

final class Calendar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'bites-calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected static ?string $slug = 'calendar';

    protected string $view = 'bites::calendar';

    public array $events = [];

    public ?string $shiftCode = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return true; // auth()->user()?->can('time.calendar.access') ?? false;
    }

    public static function canManage(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $staff = $user->staff;

        return $staff?->hasAnyPermission('manage.time.manage-calendar');
    }

    public function mount(
        CalendarEventService $calendarEvents,
        ShiftGeneratorService $shiftGenerator,
    ): void {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $role = $shiftGenerator->roleFor($user);
        $this->shiftCode = $role
            ? $shiftGenerator->codeFromRole($role)
            : null;

        $timezone = (string) config(
            'bites.time.timezone',
            'Asia/Kuala_Lumpur',
        );

        $now = CarbonImmutable::now($timezone);

        $from = $now->subDays(
            (int) config('bites.time.calendar_past_days', 45),
        );

        $to = $now->addDays(
            (int) config('bites.time.calendar_future_days', 120),
        );

        $this->events = $calendarEvents->forUser($user, $from, $to);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => app(TimeJsonRepository::class)->all('holidays'))
            ->defaultSort('date')
            ->columns([
                TextColumn::make('title')
                    ->label('Event')
                    ->searchable(),

                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => EventType::tryFrom($state)?->getLabel() ?? $state,
                    )
                    ->color(
                        fn (string $state): string|array|null => EventType::tryFrom($state)?->getColor(),
                    )
                    ->searchable(),

                TextColumn::make('description')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->paginated(false);
    }
}
