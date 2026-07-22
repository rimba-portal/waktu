<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Resources\Shifts;

use BackedEnum;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\CreateShift;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\EditShift;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\ListShifts;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Pages\ViewShift;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Schemas\ShiftForm;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Schemas\ShiftInfolist;
use Rimba\Time\Http\UI\Admin\Resources\Shifts\Tables\ShiftsTable;
use Rimba\Time\Models\Shift;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static string|UnitEnum|null $navigationGroup = 'Calendar';

    protected static string|BackedEnum|null $navigationIcon = 'bites-shift';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return ShiftForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShiftsTable::configure($table);
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
            'create' => CreateShift::route('/create'),
            'view' => ViewShift::route('/{record}'),
            'edit' => EditShift::route('/{record}/edit'),
        ];
    }
}
