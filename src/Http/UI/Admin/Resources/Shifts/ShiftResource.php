<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\ListShifts;
use Rimba\Time\Models\Shift;
use UnitEnum;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|UnitEnum|null $navigationGroup = 'Time';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-play';

    protected static ?int $navigationSort = 26;

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
            'index' => ListShifts::route('/'),
            // 'create' => \Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\CreateShift::route('/create'),
            // 'view' => \Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\ViewShift::route('/{record}'),
            // 'edit' => \Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\EditShift::route('/{record}/edit'),
            //
        ];
    }
}
