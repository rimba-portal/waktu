<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Team\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageOverrides extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-override';

    protected static string|UnitEnum|null $navigationGroup = 'Resources';

    protected static ?string $navigationLabel = 'Shift Overrides';

    protected static ?string $title = 'Shift Overrides';

    protected ?string $subheading =
        'Manage date-specific exceptions to normal shift schedules.';

    protected static function collection(): string
    {
        return 'overrides';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('records')
                    ->hiddenLabel()
                    ->schema([
                        Section::make('Override')
                            ->description(
                                'Select the affected role, date, and override action.',
                            )
                            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
                            ->schema([
                                TextInput::make('role')
                                    ->label('Shift role')
                                    ->placeholder('staff◇shift_code◇X-4G3S')
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
                            ->icon(Heroicon::OutlinedChatBubbleBottomCenterText)
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
            ]);
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
