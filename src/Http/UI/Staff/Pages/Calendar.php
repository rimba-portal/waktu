<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Staff\Pages;

use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rimba\Time\Enums\EventType;
use Rimba\Time\Models\Event;
use Rimba\Time\Services\ShiftPattern;
use UnitEnum;

class Calendar extends Page implements HasActions, HasForms, HasTable
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|UnitEnum|null $navigationGroup = 'ToDo';

    protected static string|BackedEnum|null $navigationIcon = 'rimba-s-calendar';

    protected static ?string $navigationLabel = 'Calendar';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Calendar';

    protected ?string $subheading = 'Calendar view of workdays, holidays and events.';

    protected string $view = 'bites.calendar';

    public $events;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Event::query()
            )
            ->paginated(['all'])
            ->columns([
                TextColumn::make('title')->label('Title'),
                TextColumn::make('description')->label('Description'),
                // ColorColumn::make('color')->label('Event Color')->sortable(),

                ColorColumn::make('event_type_color')
                    ->label('Event Color')
                    ->state(fn (Event $record): ?string => $this->getEventColor($record)),
                TextColumn::make('starts_at')->date('D M j, Y')->label('Date')->sortable(),
            ])
            ->groups([
                // Group by Month/Year from starts_at
                Group::make('starts_at')
                    ->label('Month')
                    ->getTitleFromRecordUsing(fn (Event $record) => optional($record->starts_at)?->isoFormat('MMMM • YYYY') ?? 'No Date')
                    ->getKeyFromRecordUsing(fn (Event $record) => optional($record->starts_at)?->format('Y-m') ?? '0000-00')
                    ->collapsible(),

                Group::make('iso_week')
                    ->label('Week')
                    ->getTitleFromRecordUsing(fn (Event $record): string => $record->starts_at ? sprintf('%s • %s', $record->starts_at->format('W'), $record->starts_at->format('o')) : 'No Date')
                    ->getKeyFromRecordUsing(fn (Event $record) => optional($record->starts_at)?->format('Y-m') ?? '0000-00')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('starts_at', $direction))
                    ->collapsible(),

            ])
            ->filters([
                SelectFilter::make('type')
                    ->multiple()
                    ->options(
                        collect(EventType::cases())->mapWithKeys(function ($case): array {
                            return [$case->value => $case->getLabel()];
                        })->toArray()
                    ),
            ])
            ->recordActions([

                EditAction::make()
                    ->schema([
                        Forms\Components\Select::make('type')->label('Event Type')
                            ->options(
                                collect(EventType::cases())->mapWithKeys(function ($case): array {
                                    return [$case->value => $case->getLabel()];
                                })->toArray()
                            )
                            ->required()
                            ->live() // make it reactive
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (blank($state)) {
                                    $set('color', null);

                                    return;
                                }

                                // Map the selected string value back to enum and set color
                                $set('color', EventType::from($state)->getColor()[300]);
                            }),
                        Forms\Components\ColorPicker::make('color')
                            ->placeholder(null)
                            ->label('Event Color')
                            ->disabled()     // read-only in UI
                            ->dehydrated(),  // still gets saved to the database
                    ]),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->model(Event::class)
                    ->createAnother(false)
                    ->schema([
                        Forms\Components\TextInput::make('title')->label('Title'),
                        Flex::make([
                            Forms\Components\DateTimePicker::make('starts_at')->label('Start Date'),
                            Forms\Components\DateTimePicker::make('ends_at')->label('End Date'),
                            Forms\Components\Select::make('type')->label('Event Type')
                                ->options(
                                    collect(EventType::cases())->mapWithKeys(function ($case): array {
                                        return [$case->value => $case->getLabel()];
                                    })->toArray()
                                )
                                ->required()
                                ->live() // make it reactive
                                ->afterStateUpdated(function ($state, Set $set): void {
                                    if (blank($state)) {
                                        $set('color', null);

                                        return;
                                    }

                                    // Map the selected string value back to enum and set color
                                    $set('color', EventType::from($state)->getColor()[300]);
                                }),
                            Forms\Components\ColorPicker::make('color')
                                ->placeholder(null)
                                ->label('Event Color')
                                ->disabled()     // read-only in UI
                                ->dehydrated(),  // still gets saved to the database
                        ]),
                    ]),
            ]);
    }

    protected function getEventColor(Event $event, int $shade = 300): ?string
    {
        return EventType::tryFrom($event->type)?->getColor()[$shade] ?? null;
    }

    public function render(): View
    {
        // Returns a LengthAwarePaginator of the *current page* after filters/search/sort
        $paginator = $this->getTableRecords();

        $public_events = collect($paginator->items())->map(function (Event $event): array {
            return [
                'title' => $event->title,
                'start' => $event->starts_at?->toIso8601String(),
                'end' => $event->ends_at?->toIso8601String(),
                'color' => $this->getEventColor($event),
                'allDay' => $event->is_all_day,
            ];
        })->values();

        $shiftEvents = collect();

        $scode = Auth::user()?->staff?->shiftCode;

        if (filled($scode)) {
            [$shiftGroup, $shiftPattern] = explode('-', $scode, 2);

            $patterns = array_keys(config('shift_pattern.patterns', []));
            $foundPattern = null;

            foreach ($patterns as $key) {
                $pattern = ShiftPattern::fromConfig($key);

                if ($pattern->hasTeam($shiftGroup)) {
                    $foundPattern = $pattern;
                    break;
                }
            }

            if ($foundPattern instanceof ShiftPattern) {
                $tz = config(
                    sprintf(
                        'shift_pattern.patterns.%s.timezone',
                        $foundPattern->getPatternKey()
                    ),
                    config('app.timezone', 'Asia/Kuala_Lumpur')
                );

                $now = Carbon::now($tz);
                $start = $now->copy()->startOfMonth()->subDays(7);
                $end = $now->copy()->endOfMonth()->addDays(7);

                $shiftEvents = collect(
                    $foundPattern->eventsForTeamInRange($shiftGroup, $start, $end)
                );
            }
        }

        $events = $shiftEvents->concat($public_events)->values();
        // dd($events->take(3)->toArray());
        $this->events = $events->toJson();

        // dd($this->getTableRecords());
        return parent::render();
    }
}
