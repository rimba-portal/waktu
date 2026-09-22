<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageOverrides extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static ?string $navigationLabel = 'Shift Overrides';

    protected static ?string $title = 'Shift Overrides';

    protected static function collection(): string
    {
        return 'overrides';
    }

    protected function recordSchema(): array
    {
        return [
            TextInput::make('uid')
                ->required(),
            TextInput::make('role')
                ->required()
                ->placeholder('shift_code.X-4G3S'),
            DatePicker::make('date')
                ->required(),
            Select::make('action')
                ->options([
                    'force_shift' => 'Force shift',
                    'skip' => 'Skip',
                    'holiday' => 'Holiday',
                    'rest' => 'Rest',
                ])
                ->required(),
            TextInput::make('shift_code'),
            TextInput::make('title'),
            TextInput::make('start_time'),
            TextInput::make('end_time'),
            ColorPicker::make('color'),
            Textarea::make('reason')
                ->columnSpanFull(),
            KeyValue::make('attributes')
                ->columnSpanFull(),
        ];
    }
}
