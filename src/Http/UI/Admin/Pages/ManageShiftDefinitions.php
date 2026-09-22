<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageShiftDefinitions extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static ?string $navigationLabel = 'Shift Definitions';

    protected static ?string $title = 'Shift Definitions';

    protected static function collection(): string
    {
        return 'shift-definitions';
    }

    protected function recordSchema(): array
    {
        return [
            TextInput::make('code')
                ->required(),
            TextInput::make('name')
                ->required(),
            Select::make('type')
                ->options([
                    'fixed' => 'Fixed weekly',
                    'cycle' => 'Cycle',
                ])
                ->required()
                ->live(),
            Toggle::make('holiday_observed')
                ->default(true),
            TagsInput::make('days')
                ->suggestions(['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'])
                ->visible(fn (Get $g): bool => $g('type') === 'fixed'),
            TextInput::make('start_time')
                ->visible(fn (Get $g): bool => $g('type') === 'fixed'),
            TextInput::make('end_time')->visible(fn (Get $g): bool => $g('type') === 'fixed'),
            ColorPicker::make('color'),
            TextInput::make('anchor_date')
                ->visible(fn (Get $g): bool => $g('type') === 'cycle'),
            TextInput::make('offset')
                ->numeric()
                ->default(0)
                ->visible(fn (Get $g): bool => $g('type') === 'cycle'),
            TagsInput::make('cycle')
                ->visible(fn (Get $g): bool => $g('type') === 'cycle')
                ->helperText('One shift code per cycle day, e.g. M,M,M,M,M,M,R,R'),
            Repeater::make('shifts')
                ->visible(fn (Get $g): bool => $g('type') === 'cycle')
                ->schema([
                    TextInput::make('code')
                        ->required(),
                    TextInput::make('name')
                        ->required(),
                    TextInput::make('start_time'),
                    TextInput::make('end_time'),
                    Toggle::make('working')
                        ->default(true),
                    ColorPicker::make('color'),
                ])
                ->columns(3)
                ->columnSpanFull(),
            KeyValue::make('attributes')
                ->columnSpanFull(),
        ];
    }
}
