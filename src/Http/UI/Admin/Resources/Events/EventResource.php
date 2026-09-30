<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Events;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Rimba\Time\Http\UI\Admin\Resources\Events\Pages\ListEvents;
use Rimba\Time\Models\Event;
use UnitEnum;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-play';

    protected static ?int $navigationSort = 25;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
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
            // 'create' => \Rimba\Time\Http\UI\Admin\Resources\Events\Pages\CreateEvent::route('/create'),
            // 'view' => \Rimba\Time\Http\UI\Admin\Resources\Events\Pages\ViewEvent::route('/{record}'),
            // 'edit' => \Rimba\Time\Http\UI\Admin\Resources\Events\Pages\EditEvent::route('/{record}/edit'),
            //
        ];
    }
}
