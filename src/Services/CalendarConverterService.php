<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CalendarConverterService
{
    public function jsonToArray(string $content): array
    {
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($data)) {
            throw new InvalidArgumentException('JSON must contain an array.');
        }

        return array_values(array_filter($data, 'is_array'));
    }

    public function holidaysFromIcs(string $content): array
    {
        $content = preg_replace("/\r?\n[ \t]/", '', $content) ?? $content;
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $rows = [];
        $event = null;
        $tz = (string) config('bites.time.timezone', 'UTC');
        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }[$left,$value] = explode(':', $line, 2);
            $parts = explode(';', $left);
            $key = strtoupper(array_shift($parts));
            $params = [];
            foreach ($parts as $part) {
                if (str_contains($part, '=')) {
                    [$k,$v] = explode('=', $part, 2);
                    $params[strtoupper($k)] = $v;
                }
            }

            if ($key === 'X-WR-TIMEZONE') {
                $tz = $value;
            }if ($key === 'BEGIN' && $value === 'VEVENT') {
                $event = ['attributes' => []];

                continue;
            }if ($key === 'END' && $value === 'VEVENT' && is_array($event)) {
                $event['uid'] ??= Str::uuid().'@rimba';
                $event['title'] ??= 'Holiday';
                $event['type'] ??= 'Paid Public Holiday';
                $event['status'] ??= 'confirmed';
                $event['color'] ??= '#f97316';
                $rows[] = $event;
                $event = null;

                continue;
            }if (! is_array($event)) {
                continue;
            }

            if ($key === 'UID') {
                $event['uid'] = $value;
            } elseif ($key === 'SUMMARY') {
                $event['title'] = $this->unescape($value);
            } elseif ($key === 'DESCRIPTION') {
                $event['description'] = $this->unescape($value);
            } elseif ($key === 'CATEGORIES') {
                $event['type'] = $value;
            } elseif ($key === 'STATUS') {
                $event['status'] = strtolower($value);
            } elseif ($key === 'DTSTART') {
                $isDate = ($params['VALUE'] ?? null) === 'DATE' || preg_match('/^\d{8}$/', $value);
                $event['date'] = $isDate ? CarbonImmutable::createFromFormat('Ymd', substr($value, 0, 8), $tz)->format('Y-m-d') : CarbonImmutable::parse($value, $params['TZID'] ?? $tz)->format('Y-m-d');
            } else {
                $event['attributes'][$key] = $value;
            }
        }

        return $rows;
    }

    public function holidaysToIcs(array $rows): string
    {
        $tz = (string) config('bites.time.timezone', 'UTC');
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Rimba Waktu//EN', 'CALSCALE:GREGORIAN', 'X-WR-CALNAME:Rimba Holidays', 'X-WR-TIMEZONE:'.$tz];
        foreach ($rows as $row) {
            $date = CarbonImmutable::parse($row['date']);
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.($row['uid'] ?? Str::uuid().'@rimba');
            $lines[] = 'DTSTAMP:'.gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$date->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$date->addDay()->format('Ymd');
            $lines[] = 'SUMMARY:'.$this->escape((string) ($row['title'] ?? 'Holiday'));
            $lines[] = 'CATEGORIES:'.$this->escape((string) ($row['type'] ?? 'Holiday'));
            $lines[] = 'STATUS:'.strtoupper((string) ($row['status'] ?? 'confirmed'));
            $lines[] = 'END:VEVENT';
        }$lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', ';', ',', "\n"], ['\\\\', '\\;', '\\,', '\\n'], $s);
    }

    private function unescape(string $s): string
    {
        return str_replace(['\\n', '\\,', '\\;', '\\\\'], ["\n", ',', ';', '\\'], $s);
    }
}
