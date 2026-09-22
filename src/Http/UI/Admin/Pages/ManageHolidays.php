<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Rimba\Time\Enums\EventType;
use UnitEnum;

final class ManageHolidays extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static ?string $navigationLabel = 'Holidays';

    protected static ?string $title = 'Holidays';

    protected static function collection(): string
    {
        return 'holidays';
    }

    protected function supportsIcs(): bool
    {
        return true;
    }

    protected function recordSchema(): array
    {
        return [
            TextInput::make('uid')
                ->required(),

            TextInput::make('title')
                ->required(),

            DatePicker::make('date')
                ->required(),

            Select::make('type')
                ->options(EventType::class)
                ->live()
                ->required(),
            KeyValue::make('attributes')
                ->columnSpanFull(),
        ];
    }
}
