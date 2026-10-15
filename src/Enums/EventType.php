<?php

declare(strict_types=1);

namespace Rimba\Time\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EventType: string implements HasColor, HasDescription, HasLabel
{
    case PaidPublicHoliday = 'Paid Public Holiday';
    case UnpaidPublicHoliday = 'Unpaid Public Holiday';
    case InLieuRestDay = 'In-Lieu Rest Day';
    case CollectiveAnnualLeave = 'Collective Annual Leave';
    case SatOffDays = 'Saturday Off Days';
    case SatReplacementLeave = 'Saturday Replacement Leave';
    case ATMActivity = 'ATM Activity';
    case Others = 'Others';

    public function getLabel(): string|Htmlable|null
    {
        return $this->value;
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::PaidPublicHoliday => 'This is a paid public holiday.',
            self::UnpaidPublicHoliday => 'This is an unpaid public holiday.',
            self::InLieuRestDay => 'This is an in-lieu rest day.',
            self::CollectiveAnnualLeave => 'This is collective annual leave.',
            self::SatOffDays => 'This is a Saturday off day for shift workers.',
            self::SatReplacementLeave => 'This is a Saturday replacement leave.',
            self::ATMActivity => 'This is an ATM activity day.',
            self::Others => 'This is another event type.',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PaidPublicHoliday => Color::Orange,
            self::UnpaidPublicHoliday => Color::Red,
            self::InLieuRestDay => Color::Yellow,
            self::CollectiveAnnualLeave => Color::Cyan,
            self::SatOffDays => Color::Green,
            self::SatReplacementLeave => Color::Fuchsia,
            self::ATMActivity => Color::Blue,
            self::Others => Color::Zinc,
        };
    }
}
