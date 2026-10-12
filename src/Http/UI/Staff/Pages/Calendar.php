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

    public ?string $shiftRole = null;

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    */

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public static function canManage(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('waktu.manage')
            || $user->can('waktu.holidays')
            || $user->can('waktu.shift-definitions')
            || $user->can('waktu.overrides');
    }

    /*
    |--------------------------------------------------------------------------
    | Page
    |--------------------------------------------------------------------------
    */

    public function mount(
        CalendarEventService $events,
        ShiftGeneratorService $generator,
    ): void {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $role = $generator->roleFor($user);

        $this->shiftRole = $role
            ? $this->displayShiftRole($role)
            : null;

        $timezone = (string) config(
            'bites.time.timezone',
            'Asia/Kuala_Lumpur',
        );

        $from = CarbonImmutable::now($timezone)
            ->subDays(
                (int) config(
                    'bites.time.calendar_past_days',
                    45,
                )
            );

        $to = CarbonImmutable::now($timezone)
            ->addDays(
                (int) config(
                    'bites.time.calendar_future_days',
                    120,
                )
            );

        $this->events = $events->forUser(
            $user,
            $from,
            $to,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Holidays Table
    |--------------------------------------------------------------------------
    */

    public function table(Table $table): Table
    {
        return $table
            ->records(
                fn (): array => app(TimeJsonRepository::class)
                    ->all('holidays')
            )
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
                    ->searchable(),

                TextColumn::make('description')
                    ->placeholder('—')
                    ->wrap(),
            ])
            ->paginated(false);
    }

    /*
    |--------------------------------------------------------------------------
    | Shift Role
    |--------------------------------------------------------------------------
    */

    private function displayShiftRole(string $role): string
    {
        $prefix = (string) config(
            'bites.time.role_prefix',
            'staff◇shift_code◇',
        );

        return str_starts_with($role, $prefix)
            ? substr($role, strlen($prefix))
            : $role;
    }
}
