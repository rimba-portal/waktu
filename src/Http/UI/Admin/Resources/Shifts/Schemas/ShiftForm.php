<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class ShiftForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('org_unit_id')
                    ->relationship('orgUnit', 'name'),
                Select::make('org_team_id')
                    ->relationship('orgTeam', 'name'),
                Select::make('staff_id')
                    ->relationship('staff', 'name'),
                TextInput::make('type'),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TimePicker::make('start_time')
                    ->required(),
                TimePicker::make('end_time')
                    ->required(),
                DatePicker::make('start_date'),
                DatePicker::make('end_date'),
                Textarea::make('attributes')
                    ->columnSpanFull(),
            ]);
    }
}
