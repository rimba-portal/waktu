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

final class ManageHolidays extends ManageJsonCalendar
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static ?string $navigationLabel = 'Holidays';

    protected static ?string $title = 'Holidays';

    protected static function collection(): string
    {
        return 'holidays';
    }

    protected function recordSchema(): array
    {
        return [
            TextInput::make('uid')
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('title')
                ->required(),
            DatePicker::make('date')
                ->required(),
            Select::make('type')
                ->options([
                    'Paid Public Holiday' => 'Paid Public Holiday',
                    'Unpaid Public Holiday' => 'Unpaid Public Holiday',
                    'In-Lieu Rest Day' => 'In-Lieu Rest Day',
                    'Collective Annual Leave' => 'Collective Annual Leave',
                    'Saturday Off Day' => 'Saturday Off Day',
                    'Saturday Replacement Leave' => 'Saturday Replacement Leave',
                    'Other' => 'Other',
                ])
                ->required(),
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
