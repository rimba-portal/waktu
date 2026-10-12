<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Team\Pages;

use BackedEnum;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageShiftDefinitions extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-shift';

    protected static string|UnitEnum|null $navigationGroup = 'Resources';

    protected static ?string $navigationLabel = 'Shift Definitions';

    protected static ?string $title = 'Shift Definitions';

    protected ?string $subheading =
        'Design fixed weekly schedules and rotating shift cycles.';

    protected static function collection(): string
    {
        return 'shift-definitions';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('records')
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
                                    ->label('Default color'),
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
                                            ),

                                        TimePicker::make('end_time')
                                            ->hiddenLabel()
                                            ->seconds(false)
                                            ->disabled(
                                                fn (Get $get): bool => ! $get('working'),
                                            ),

                                        Toggle::make('working')
                                            ->hiddenLabel()
                                            ->live()
                                            ->default(true),

                                        ColorPicker::make('color')
                                            ->hiddenLabel(),
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
            ]);
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
}
