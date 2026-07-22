<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('org_unit_id')
                    ->relationship('orgUnit', 'name'),
                TextInput::make('owner_id')
                    ->numeric(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Toggle::make('is_all_day')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at'),
                TextInput::make('timezone'),
                DateTimePicker::make('start_UTC'),
                DateTimePicker::make('end_UTC'),
                TextInput::make('type'),
                TextInput::make('status')
                    ->required()
                    ->default('planned'),
                TextInput::make('color'),
            ]);
    }
}
