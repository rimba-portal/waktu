<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Team\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Rimba\Time\Enums\EventType;
use Rimba\Time\Services\CalendarConverterService;
use Rimba\Time\Services\TimeJsonRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use UnitEnum;

final class ManageCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-calendar';

    protected static string|UnitEnum|null $navigationGroup = 'Resources';

    protected static ?string $navigationLabel = 'Manage Time';

    protected static ?string $title = 'Manage Time';

    protected ?string $subheading =
        'Manage holidays, shift definitions, and date-specific shift overrides.';

    protected string $view = 'bites::manage-json-collection';

    /**
     * All three JSON collections use one Livewire form state.
     *
     * @var array{
     *     holidays: array<int, array<string, mixed>>,
     *     shift_definitions: array<int, array<string, mixed>>,
     *     overrides: array<int, array<string, mixed>>
     * }
     */
    public ?array $data = [
        'holidays' => [],
        'shift_definitions' => [],
        'overrides' => [],
    ];

    public function mount(TimeJsonRepository $repository): void
    {
        $this->refreshAll($repository);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('time-management')
                    ->tabs([
                        $this->holidaysTab(),
                        $this->shiftDefinitionsTab(),
                        $this->overridesTab(),
                    ])
                    ->persistTabInQueryString('tab')
                    ->columnSpanFull(),
            ]);
    }

    /**
     * This method is retained because the existing
     * manage-json-collection Blade view submits to save().
     *
     * It saves all three collections.
     */
    public function save(TimeJsonRepository $repository): void
    {
        $repository->replace(
            'holidays',
            $this->data['holidays'] ?? [],
        );

        $repository->replace(
            'shift-definitions',
            $this->data['shift_definitions'] ?? [],
        );

        $repository->replace(
            'overrides',
            $this->data['overrides'] ?? [],
        );

        $this->refreshAll($repository);

        Notification::make()
            ->title('Time configuration saved')
            ->body('Holidays, shift definitions, and overrides were saved.')
            ->success()
            ->send();
    }

    public function saveHolidays(TimeJsonRepository $repository): void
    {
        $repository->replace(
            'holidays',
            $this->data['holidays'] ?? [],
        );

        $this->refreshCollection($repository, 'holidays');

        Notification::make()
            ->title('Holidays saved')
            ->success()
            ->send();
    }

    public function saveShiftDefinitions(
        TimeJsonRepository $repository,
    ): void {
        $repository->replace(
            'shift-definitions',
            $this->data['shift_definitions'] ?? [],
        );

        $this->refreshCollection($repository, 'shift-definitions');

        Notification::make()
            ->title('Shift definitions saved')
            ->success()
            ->send();
    }

    public function saveOverrides(TimeJsonRepository $repository): void
    {
        $repository->replace(
            'overrides',
            $this->data['overrides'] ?? [],
        );

        $this->refreshCollection($repository, 'overrides');

        Notification::make()
            ->title('Shift overrides saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveAll')
                ->label('Save All')
                ->icon(Heroicon::OutlinedCheck)
                ->color('primary')
                ->action('save'),

            Action::make('import')
                ->label('Import')
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->schema([
                    Radio::make('collection')
                        ->options([
                            'holidays' => 'Holidays',
                            'shift-definitions' => 'Shift Definitions',
                            'overrides' => 'Shift Overrides',
                        ])
                        ->inline()
                        ->required(),

                    Select::make('format')
                        ->options([
                            'json' => 'JSON',
                            'ics' => 'ICS, holidays only',
                        ])
                        ->default('json')
                        ->native(false)
                        ->live()
                        ->required(),

                    FileUpload::make('file')
                        ->disk('local')
                        ->directory('waktu-imports')
                        ->acceptedFileTypes([
                            'application/json',
                            'text/json',
                            'text/calendar',
                            'text/plain',
                        ])
                        ->required(),

                    Select::make('strategy')
                        ->options([
                            'update' => 'Update existing records',
                            'append' => 'Append new records',
                            'replace_all' => 'Replace all records',
                        ])
                        ->default('update')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (
                    array $data,
                    TimeJsonRepository $repository,
                    CalendarConverterService $converter,
                ): void {
                    $this->importCollection(
                        data: $data,
                        repository: $repository,
                        converter: $converter,
                    );
                }),

            Action::make('exportJson')
                ->label('Export JSON')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->schema([
                    Radio::make('collection')
                        ->options([
                            'holidays' => 'Holidays',
                            'shift-definitions' => 'Shift Definitions',
                            'overrides' => 'Shift Overrides',
                        ])
                        ->inline()
                        ->required(),
                ])
                ->action(
                    fn (
                        array $data,
                        TimeJsonRepository $repository,
                    ): StreamedResponse => $this->exportJson(
                        collection: (string) $data['collection'],
                        repository: $repository,
                    ),
                ),

            Action::make('exportIcs')
                ->label('Export Holidays ICS')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->action(
                    fn (
                        TimeJsonRepository $repository,
                        CalendarConverterService $converter,
                    ): StreamedResponse => $this->exportHolidaysIcs(
                        repository: $repository,
                        converter: $converter,
                    ),
                ),
        ];
    }

    private function holidaysTab(): Tab
    {
        return Tab::make('Holidays')
            ->icon('bites-event')
            ->badge(
                fn (): int => count($this->data['holidays'] ?? []),
            )
            ->schema([
                Section::make('Calendar Events')
                    ->description(
                        'Manage public holidays, collective leave, replacement leave, and company activities.',
                    )
                    ->headerActions([
                        Action::make('saveHolidays')
                            ->label('Save Holidays')
                            ->icon(Heroicon::OutlinedCheck)
                            ->action('saveHolidays'),
                    ])
                    ->schema([
                        Repeater::make('holidays')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Title'),
                                TableColumn::make('Date')
                                    ->width('160px'),
                                TableColumn::make('Type')
                                    ->width('220px'),
                                TableColumn::make('Description'),
                                TableColumn::make('Attributes'),
                            ])
                            ->schema([
                                TextInput::make('title')
                                    ->hiddenLabel()
                                    ->required(),

                                DatePicker::make('date')
                                    ->hiddenLabel()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->required(),

                                Select::make('type')
                                    ->hiddenLabel()
                                    ->options(EventType::class)
                                    ->native(false)
                                    ->required(),

                                TextInput::make('description')
                                    ->hiddenLabel(),

                                KeyValue::make('attributes')
                                    ->hiddenLabel()
                                    ->default([]),
                            ])
                            ->itemLabel(
                                fn (?array $state): string => $this->holidayLabel($state),
                            )
                            ->addActionLabel('Add holiday')
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function shiftDefinitionsTab(): Tab
    {
        return Tab::make('Shift Definitions')
            ->icon('bites-shift')
            ->badge(
                fn (): int => count(
                    $this->data['shift_definitions'] ?? [],
                ),
            )
            ->schema([
                Section::make('Shift Definitions')
                    ->description(
                        'Design fixed weekly schedules and rotating shift cycles.',
                    )
                    ->headerActions([
                        Action::make('saveShiftDefinitions')
                            ->label('Save Shift Definitions')
                            ->icon(Heroicon::OutlinedCheck)
                            ->action('saveShiftDefinitions'),
                    ])
                    ->schema([
                        Repeater::make('shift_definitions')
                            ->hiddenLabel()
                            ->schema([
                                Section::make('Definition')
                                    ->description(
                                        'Identify the schedule and select how it is generated.',
                                    )
                                    ->icon(Heroicon::OutlinedIdentification)
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('Code')
                                            ->placeholder('1-Normal')
                                            ->helperText(
                                                'Unique code used for shift-role resolution.',
                                            )
                                            ->required(),

                                        TextInput::make('name')
                                            ->label('Name')
                                            ->placeholder('Normal Hours')
                                            ->required(),

                                        Select::make('type')
                                            ->label('Schedule type')
                                            ->options([
                                                'fixed' => 'Fixed weekly schedule',
                                                'cycle' => 'Rotating cycle',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->required(),

                                        Toggle::make('holiday_observed')
                                            ->label('Observe holidays')
                                            ->helperText(
                                                'Do not generate a shift when an applicable holiday exists.',
                                            )
                                            ->default(true),

                                        ColorPicker::make('color')
                                            ->label('Calendar color')
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'fixed',
                                            ),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Fixed Weekly Schedule')
                                    ->description(
                                        'Choose the recurring working days and daily hours.',
                                    )
                                    ->icon(Heroicon::OutlinedCalendarDays)
                                    ->visible(
                                        fn (Get $get): bool => $get('type') === 'fixed',
                                    )
                                    ->schema([
                                        CheckboxList::make('days')
                                            ->label('Working days')
                                            ->options([
                                                'MON' => 'Monday',
                                                'TUE' => 'Tuesday',
                                                'WED' => 'Wednesday',
                                                'THU' => 'Thursday',
                                                'FRI' => 'Friday',
                                                'SAT' => 'Saturday',
                                                'SUN' => 'Sunday',
                                            ])
                                            ->columns([
                                                'default' => 2,
                                                'sm' => 4,
                                                'lg' => 7,
                                            ])
                                            ->gridDirection('row')
                                            ->bulkToggleable()
                                            ->required()
                                            ->columnSpanFull(),

                                        TimePicker::make('start_time')
                                            ->label('Start time')
                                            ->seconds(false)
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'fixed',
                                            ),

                                        TimePicker::make('end_time')
                                            ->label('End time')
                                            ->seconds(false)
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'fixed',
                                            ),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Cycle Configuration')
                                    ->description(
                                        'Set the cycle origin and sequence of shift codes.',
                                    )
                                    ->icon(Heroicon::OutlinedArrowPath)
                                    ->visible(
                                        fn (Get $get): bool => $get('type') === 'cycle',
                                    )
                                    ->schema([
                                        DatePicker::make('anchor_date')
                                            ->label('Anchor date')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->helperText(
                                                'The date from which cycle positions are calculated.',
                                            )
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'cycle',
                                            ),

                                        TextInput::make('offset')
                                            ->label('Cycle offset')
                                            ->numeric()
                                            ->integer()
                                            ->default(0)
                                            ->helperText(
                                                'Adjusts the starting position in the cycle.',
                                            )
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'cycle',
                                            ),

                                        TagsInput::make('cycle')
                                            ->label('Cycle sequence')
                                            ->placeholder('Add a shift code')
                                            ->helperText(
                                                'Enter one code per cycle day, for example: M, M, M, M, M, M, R, R.',
                                            )
                                            ->required(
                                                fn (Get $get): bool => $get('type') === 'cycle',
                                            )
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Cycle Shift Codes')
                                    ->description(
                                        'Define every working and rest code used by the cycle sequence.',
                                    )
                                    ->icon(Heroicon::OutlinedClock)
                                    ->visible(
                                        fn (Get $get): bool => $get('type') === 'cycle',
                                    )
                                    ->schema([
                                        Repeater::make('shifts')
                                            ->hiddenLabel()
                                            ->table([
                                                TableColumn::make('Code')
                                                    ->width('90px'),
                                                TableColumn::make('Name'),
                                                TableColumn::make('Start')
                                                    ->width('140px'),
                                                TableColumn::make('End')
                                                    ->width('140px'),
                                                TableColumn::make('Working')
                                                    ->width('100px'),
                                                TableColumn::make('Color')
                                                    ->width('100px'),
                                            ])
                                            ->schema([
                                                TextInput::make('code')
                                                    ->hiddenLabel()
                                                    ->placeholder('M')
                                                    ->required(),

                                                TextInput::make('name')
                                                    ->hiddenLabel()
                                                    ->placeholder('Morning')
                                                    ->required(),

                                                TimePicker::make('start_time')
                                                    ->hiddenLabel()
                                                    ->seconds(false)
                                                    ->disabled(
                                                        fn (Get $get): bool => ! $get('working'),
                                                    )
                                                    ->required(
                                                        fn (Get $get): bool => (bool) $get('working'),
                                                    ),

                                                TimePicker::make('end_time')
                                                    ->hiddenLabel()
                                                    ->seconds(false)
                                                    ->disabled(
                                                        fn (Get $get): bool => ! $get('working'),
                                                    )
                                                    ->required(
                                                        fn (Get $get): bool => (bool) $get('working'),
                                                    ),

                                                Toggle::make('working')
                                                    ->hiddenLabel()
                                                    ->live()
                                                    ->default(true),

                                                ColorPicker::make('color')
                                                    ->hiddenLabel()
                                                    ->required(
                                                        fn (Get $get): bool => (bool) $get('working'),
                                                    ),
                                            ])
                                            ->itemLabel(
                                                fn (?array $state): string => $this->shiftLabel($state),
                                            )
                                            ->addActionLabel('Add shift code')
                                            ->reorderable(false)
                                            ->defaultItems(0)
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Advanced')
                                    ->description(
                                        'Additional metadata for this shift definition.',
                                    )
                                    ->icon(Heroicon::OutlinedCog6Tooth)
                                    ->schema([
                                        KeyValue::make('attributes')
                                            ->default([])
                                            ->addActionLabel('Add attribute')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsed()
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->itemLabel(
                                fn (?array $state): string => $this->definitionLabel($state),
                            )
                            ->addActionLabel('Add shift definition')
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function overridesTab(): Tab
    {
        return Tab::make('Shift Overrides')
            ->icon('bites-override')
            ->badge(
                fn (): int => count($this->data['overrides'] ?? []),
            )
            ->schema([
                Section::make('Shift Overrides')
                    ->description(
                        'Manage date-specific exceptions to generated shift schedules.',
                    )
                    ->headerActions([
                        Action::make('saveOverrides')
                            ->label('Save Overrides')
                            ->icon(Heroicon::OutlinedCheck)
                            ->action('saveOverrides'),
                    ])
                    ->schema([
                        Repeater::make('overrides')
                            ->hiddenLabel()
                            ->schema([
                                Section::make('Override')
                                    ->description(
                                        'Select the affected role, date, and override action.',
                                    )
                                    ->icon(
                                        Heroicon::OutlinedAdjustmentsHorizontal,
                                    )
                                    ->schema([
                                        TextInput::make('role')
                                            ->label('Shift role')
                                            ->placeholder(
                                                'staff◇shift_code◇X-4G3S',
                                            )
                                            ->helperText(
                                                'Enter the complete role used by the shift generator.',
                                            )
                                            ->required(),

                                        DatePicker::make('date')
                                            ->native(false)
                                            ->displayFormat('d M Y')
                                            ->required(),

                                        Select::make('action')
                                            ->options([
                                                'force_shift' => 'Force shift',
                                                'skip' => 'Skip scheduled shift',
                                                'holiday' => 'Holiday',
                                                'rest' => 'Rest day',
                                            ])
                                            ->native(false)
                                            ->live()
                                            ->required(),

                                        TextInput::make('title')
                                            ->placeholder('Override title')
                                            ->visible(
                                                fn (Get $get): bool => $get('action') === 'holiday',
                                            ),
                                    ])
                                    ->columns(2)
                                    ->columnSpanFull(),

                                Section::make('Forced Shift')
                                    ->description(
                                        'Define the shift that replaces the generated schedule.',
                                    )
                                    ->icon(Heroicon::OutlinedClock)
                                    ->visible(
                                        fn (Get $get): bool => $get('action') === 'force_shift',
                                    )
                                    ->schema([
                                        TextInput::make('shift_code')
                                            ->label('Shift code')
                                            ->placeholder('M')
                                            ->required(
                                                fn (Get $get): bool => $get('action') === 'force_shift',
                                            ),

                                        TimePicker::make('start_time')
                                            ->label('Start time')
                                            ->seconds(false)
                                            ->required(
                                                fn (Get $get): bool => $get('action') === 'force_shift',
                                            ),

                                        TimePicker::make('end_time')
                                            ->label('End time')
                                            ->seconds(false)
                                            ->required(
                                                fn (Get $get): bool => $get('action') === 'force_shift',
                                            ),

                                        ColorPicker::make('color')
                                            ->label('Calendar color'),
                                    ])
                                    ->columns(4)
                                    ->columnSpanFull(),

                                Section::make('Reason')
                                    ->description(
                                        'Explain why the generated schedule is being overridden.',
                                    )
                                    ->icon(
                                        Heroicon::OutlinedChatBubbleBottomCenterText,
                                    )
                                    ->schema([
                                        Textarea::make('reason')
                                            ->hiddenLabel()
                                            ->placeholder(
                                                'Reason for this schedule override...',
                                            )
                                            ->rows(3),
                                    ])
                                    ->columnSpanFull(),

                                Section::make('Advanced')
                                    ->description(
                                        'Technical identifier and additional metadata.',
                                    )
                                    ->icon(Heroicon::OutlinedCog6Tooth)
                                    ->schema([
                                        TextInput::make('uid')
                                            ->label('UID')
                                            ->placeholder(
                                                'Automatically generated when empty',
                                            )
                                            ->helperText(
                                                'Leave empty for new override records.',
                                            ),

                                        KeyValue::make('attributes')
                                            ->default([])
                                            ->addActionLabel('Add attribute')
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2)
                                    ->collapsed()
                                    ->collapsible()
                                    ->columnSpanFull(),
                            ])
                            ->columns(1)
                            ->itemLabel(
                                fn (?array $state): string => $this->overrideLabel($state),
                            )
                            ->addActionLabel('Add override')
                            ->collapsible()
                            ->cloneable()
                            ->reorderable(false)
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    private function refreshAll(TimeJsonRepository $repository): void
    {
        $this->form->fill([
            'holidays' => $repository->all('holidays'),
            'shift_definitions' => $repository->all(
                'shift-definitions',
            ),
            'overrides' => $repository->all('overrides'),
        ]);
    }

    private function refreshCollection(
        TimeJsonRepository $repository,
        string $collection,
    ): void {
        $stateKey = $this->stateKeyFor($collection);

        $this->data[$stateKey] = $repository->all($collection);
    }

    private function stateKeyFor(string $collection): string
    {
        return match ($collection) {
            'holidays' => 'holidays',
            'shift-definitions' => 'shift_definitions',
            'overrides' => 'overrides',
            default => throw new \InvalidArgumentException(
                "Unsupported time collection: {$collection}",
            ),
        };
    }

    private function importCollection(
        array $data,
        TimeJsonRepository $repository,
        CalendarConverterService $converter,
    ): void {
        $collection = (string) $data['collection'];
        $format = (string) $data['format'];
        $strategy = (string) $data['strategy'];
        $path = (string) $data['file'];

        if ($format === 'ics' && $collection !== 'holidays') {
            Notification::make()
                ->title('ICS import is only available for holidays')
                ->danger()
                ->send();

            return;
        }

        try {
            $content = Storage::disk('local')->get($path);

            $records = match ($format) {
                'ics' => $converter->holidaysFromIcs($content),
                default => $converter->jsonToArray($content),
            };

            $repository->merge(
                $collection,
                $records,
                $strategy,
            );

            $this->refreshCollection($repository, $collection);

            Notification::make()
                ->title('Import completed')
                ->body(
                    sprintf(
                        '%d record(s) were read for %s.',
                        count($records),
                        str_replace('-', ' ', $collection),
                    ),
                )
                ->success()
                ->send();
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    private function exportJson(
        string $collection,
        TimeJsonRepository $repository,
    ): StreamedResponse {
        return response()->streamDownload(
            fn (): int => print json_encode(
                $repository->all($collection),
                JSON_PRETTY_PRINT
                    | JSON_UNESCAPED_SLASHES
                    | JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR,
            ),
            "{$collection}.json",
            [
                'Content-Type' => 'application/json; charset=utf-8',
            ],
        );
    }

    private function exportHolidaysIcs(
        TimeJsonRepository $repository,
        CalendarConverterService $converter,
    ): StreamedResponse {
        $ics = $converter->holidaysToIcs(
            $repository->all('holidays'),
        );

        return response()->streamDownload(
            fn (): int => print $ics,
            'holidays.ics',
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
            ],
        );
    }

    private function holidayLabel(?array $state): string
    {
        if (! $state) {
            return 'New holiday';
        }

        $date = $state['date'] ?? 'No date';
        $title = $state['title'] ?? 'Unnamed holiday';

        return "{$date} · {$title}";
    }

    private function definitionLabel(?array $state): string
    {
        if (! $state) {
            return 'New shift definition';
        }

        $code = $state['code'] ?? 'No code';
        $name = $state['name'] ?? 'Unnamed definition';

        $type = match ($state['type'] ?? null) {
            'fixed' => 'Fixed',
            'cycle' => 'Cycle',
            default => 'Unconfigured',
        };

        return "{$code} · {$name} · {$type}";
    }

    private function shiftLabel(?array $state): string
    {
        if (! $state) {
            return 'New shift code';
        }

        $code = $state['code'] ?? 'No code';
        $name = $state['name'] ?? 'Unnamed shift';

        return "{$code} · {$name}";
    }

    private function overrideLabel(?array $state): string
    {
        if (! $state) {
            return 'New override';
        }

        $role = $state['role'] ?? 'Unassigned role';
        $date = $state['date'] ?? 'No date';

        $action = match ($state['action'] ?? null) {
            'force_shift' => 'Force shift',
            'skip' => 'Skip shift',
            'holiday' => 'Holiday',
            'rest' => 'Rest day',
            default => 'Override',
        };

        return "{$date} · {$role} · {$action}";
    }
}
