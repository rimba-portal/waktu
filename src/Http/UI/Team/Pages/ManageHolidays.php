<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Team\Pages;

use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Rimba\Time\Enums\EventType;
use UnitEnum;

final class ManageHolidays extends ManageJsonCollection
{
    protected static string|BackedEnum|null $navigationIcon = 'bites-event';

    protected static string|UnitEnum|null $navigationGroup = 'Resources';

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

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('records')
                    ->hiddenLabel()
                    ->table([
                        Repeater\TableColumn::make('Title'),
                        Repeater\TableColumn::make('Date'),
                        Repeater\TableColumn::make('Type'),
                        Repeater\TableColumn::make('Description'),
                        Repeater\TableColumn::make('Attributes'),
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->hiddenLabel()
                            ->required(),

                        DatePicker::make('date')
                            ->hiddenLabel()
                            ->required(),

                        Select::make('type')
                            ->hiddenLabel()
                            ->options(EventType::class)
                            ->required(),

                        TextInput::make('description')
                            ->hiddenLabel(),

                        KeyValue::make('attributes')
                            ->hiddenLabel(),
                    ])
                    ->addActionLabel('Add holiday')
                    ->reorderable(false)
                    ->defaultItems(0),
            ]);
    }
}
