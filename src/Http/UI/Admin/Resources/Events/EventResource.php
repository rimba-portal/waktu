<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Rimba\Time\Http\UI\Admin\Resources\Events\Pages\CreateEvent;
use Rimba\Time\Http\UI\Admin\Resources\Events\Pages\EditEvent;
use Rimba\Time\Http\UI\Admin\Resources\Events\Pages\ListEvents;
use Rimba\Time\Http\UI\Admin\Resources\Events\Pages\ViewEvent;
use Rimba\Time\Http\UI\Admin\Resources\Events\Schemas\EventForm;
use Rimba\Time\Http\UI\Admin\Resources\Events\Schemas\EventInfolist;
use Rimba\Time\Http\UI\Admin\Resources\Events\Tables\EventsTable;
use Rimba\Time\Models\Event;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|UnitEnum|null $navigationGroup = 'Calendar';

    protected static string|BackedEnum|null $navigationIcon = 'bites-event';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return EventForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EventInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EventsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvents::route('/'),
            'create' => CreateEvent::route('/create'),
            'view' => ViewEvent::route('/{record}'),
            'edit' => EditEvent::route('/{record}/edit'),
        ];
    }
}
