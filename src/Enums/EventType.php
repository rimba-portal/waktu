<?php

declare(strict_types=1);

namespace Rimba\Time\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum EventType: string implements HasDescription, HasLabel
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
            self::Others => 'This is an other event type.',

        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PaidPublicHoliday => Color::Orange,
            self::UnpaidPublicHoliday => Color::Red,
            self::CollectiveAnnualLeave => Color::Cyan,
            self::InLieuRestDay => Color::Yellow,
            self::SatOffDays => Color::Green,
            self::SatReplacementLeave => Color::Fuchsia,
            self::ATMActivity => Color::Blue,
            self::Others => Color::Zinc,
        };
    }

    /**
     * Return an OKLCH CSS string, e.g. "oklch(0.7021 0.1203 52.1349)".
     *
     * - If getColor() returns a Filament palette (array), uses $shade (default 500),
     *   then falls back to 500, then to the first available shade.
     * - If getColor() returns a string, assumes it's a hex color "#RRGGBB" or "RRGGBB".
     * - Returns null if no valid color can be resolved.
     */
    public function toOklch(int $shade = 500): ?string
    {
        $color = $this->getColor();

        if ($color === null) {
            return null;
        }

        // Resolve to a hex string
        $hex = null;

        if (is_array($color)) {
            // Filament palette: attempt requested shade, then 500, then first entry
            $candidate = $color[$shade] ?? ($color[500] ?? (is_array($color) ? reset($color) : null));
            if (is_string($candidate)) {
                $hex = $candidate;
            }
        } elseif (is_string($color)) {
            $hex = $color;
        }

        if (! is_string($hex)) {
            return null;
        }

        $hex = ltrim($hex, '#');
        if (! preg_match('/^[0-9A-Fa-f]{6}$/', $hex)) {
            return null;
        }

        return self::hexToOklchCss($hex);
    }

    /**
     * Convert a hex string "RRGGBB" to an OKLCH CSS string "oklch(L C H)".
     * Based on the OKLab/OKLCH reference conversion.
     */
    private static function hexToOklchCss(string $hex): string
    {
        // Parse hex
        $ri = hexdec(substr($hex, 0, 2));
        $gi = hexdec(substr($hex, 2, 2));
        $bi = hexdec(substr($hex, 4, 2));

        $r = $ri / 255;
        $g = $gi / 255;
        $b = $bi / 255;

        // sRGB to linear
        $r = $r <= 0.04045 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.04045 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.04045 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Linear RGB to LMS
        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        // Nonlinear transform
        $l_ = self::cuberoot($l);
        $m_ = self::cuberoot($m);
        $s_ = self::cuberoot($s);

        // OKLab
        $L = 0.2104542553 * $l_ + 0.7936177850 * $m_ - 0.0040720468 * $s_;
        $a = 1.9779984951 * $l_ - 2.4285922050 * $m_ + 0.4505937099 * $s_;
        $b2 = 0.0259040371 * $l_ + 0.7827717662 * $m_ - 0.8086757660 * $s_;

        // OKLCH
        $C = sqrt($a * $a + $b2 * $b2);
        $h = rad2deg(atan2($b2, $a));
        if ($h < 0) {
            $h += 360;
        }

        return sprintf('oklch(%.4f %.4f %.4f)', $L, $C, $h);
    }

    private static function cuberoot(float $x): float
    {
        // Preserve sign for negatives (real cube root).
        return $x < 0 ? -pow(-$x, 1 / 3) : pow($x, 1 / 3);
    }
}
