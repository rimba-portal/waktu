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
use Illuminate\Support\Facades\Storage;
use Rimba\Time\Services\CalendarEventService;
use Rimba\Time\Services\ShiftGeneratorService;
use UnitEnum;

final class Calendar extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'bites-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Todo';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?string $title = 'Calendar';

    protected string $view = 'bites::calendar';

    public array $events = [];

    public ?string $shiftRole = null;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(CalendarEventService $events, ShiftGeneratorService $generator): void
    {

        $user = auth()->user();
        $this->shiftRole = $user ? $generator->roleFor($user) : null;
        if (! $user) {
            return;
        }

        $tz = (string) config('bites.time.timezone');
        $from = CarbonImmutable::now($tz)->subDays((int) config('bites.time.calendar_past_days', 60));
        $to = CarbonImmutable::now($tz)->addDays((int) config('bites.time.calendar_future_days', 180));
        $this->events = $events->forUser($user, $from, $to);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(function (): array {
                // Read from storage/app/public/time/holidays.json
                if (! Storage::disk('public')->exists('time/holidays.json')) {
                    return [];
                }

                $json = Storage::disk('public')->get('time/holidays.json');
                $data = json_decode($json, true);

                // Ensure data returns as a flat array of objects/records
                return is_array($data) ? $data : [];
            })
            ->columns([
                // Change these keys ('name', 'date') to match the keys inside your holidays.json file
                TextColumn::make('title')
                    ->label('Event')
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->searchable(),
            ]);
    }
}
