<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

final class ManageWorkdays extends ManageJsonCalendar
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static ?string $navigationLabel = 'Workdays';

    protected static ?string $title = 'Workdays / Shifts';

    protected static function collection(): string
    {
        return 'workdays';
    }

    protected function recordSchema(): array
    {
        return [
            TextInput::make('uid')
                ->required(),
            TextInput::make('title'),
            DatePicker::make('date')
                ->required(),
            TextInput::make('team')
                ->required(),
            TextInput::make('shift_code')
                ->required(),
            TextInput::make('shift_name')
                ->required(),
            DateTimePicker::make('starts_at')
                ->seconds(false),
            DateTimePicker::make('ends_at')
                ->seconds(false)
                ->after('starts_at'),
            TextInput::make('timezone')
                ->default(config('app.timezone')),
            Select::make('status')
                ->options([
                    'planned' => 'Planned',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('confirmed')
                ->required(),
            ColorPicker::make('color'),
            Textarea::make('description')
                ->columnSpanFull(),
            KeyValue::make('attributes')
                ->columnSpanFull(),
        ];
    }
}
