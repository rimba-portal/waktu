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

    public function icsToArray(string $content, string $target): array
    {
        $content = preg_replace("/\r?\n[ \t]/", '', $content) ?? $content;
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $events = [];
        $event = null;
        $calendarTz = (string) config('app.timezone', 'UTC');
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
                $calendarTz = $value;
            }

            if ($key === 'BEGIN' && $value === 'VEVENT') {
                $event = ['attributes' => []];

                continue;
            }

            if ($key === 'END' && $value === 'VEVENT' && is_array($event)) {
                $events[] = $this->normalizeImported($event, $target, $calendarTz);
                $event = null;

                continue;
            }

            if (! is_array($event)) {
                continue;
            }

            match ($key) {
                'UID' => $event['uid'] = $value,'SUMMARY' => $event['title'] = $this->unescape($value),'DESCRIPTION' => $event['description'] = $this->unescape($value),'DTSTART' => $this->putDate($event, 'start', $value, $params, $calendarTz),'DTEND' => $this->putDate($event, 'end', $value, $params, $calendarTz),'CATEGORIES' => $event['categories'] = $value,'STATUS' => $event['status'] = strtolower($value),'COLOR','X-APPLE-CALENDAR-COLOR' => $event['color'] = $value,'X-RIMBA-TEAM' => $event['team'] = $value,'X-RIMBA-SHIFT-CODE' => $event['shift_code'] = $value,'X-RIMBA-SHIFT-NAME' => $event['shift_name'] = $value,'X-RIMBA-RECORD-TYPE' => $event['record_type'] = $value,default => $event['attributes'][$key] = $value
            };
        }

        return $events;
    }

    public function arrayToIcs(array $records, string $calendarName, string $target): string
    {
        $tz = (string) config('app.timezone', 'UTC');
        $lines = ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Rimba Waktu//Calendar//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH', 'X-WR-CALNAME:'.$this->escape($calendarName), 'X-WR-TIMEZONE:'.$tz];
        foreach ($records as $record) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.($record['uid'] ?? Str::uuid().'@rimba');
            $lines[] = 'DTSTAMP:'.gmdate('Ymd\THis\Z');
            if (($record['is_all_day'] ?? false) || ($target === 'holidays' && isset($record['date']))) {
                $start = CarbonImmutable::parse($record['date'] ?? $record['starts_at'])->format('Ymd');
                $end = CarbonImmutable::parse($record['date'] ?? $record['starts_at'])->addDay()->format('Ymd');
                $lines[] = 'DTSTART;VALUE=DATE:'.$start;
                $lines[] = 'DTEND;VALUE=DATE:'.$end;
            } else {
                $lines[] = 'DTSTART;TZID='.$tz.':'.CarbonImmutable::parse($record['starts_at'])->setTimezone($tz)->format('Ymd\THis');
                $lines[] = 'DTEND;TZID='.$tz.':'.CarbonImmutable::parse($record['ends_at'])->setTimezone($tz)->format('Ymd\THis');
            }

            $lines[] = 'SUMMARY:'.$this->escape((string) ($record['title'] ?? $record['shift_name'] ?? 'Event'));
            if (filled($record['description'] ?? null)) {
                $lines[] = 'DESCRIPTION:'.$this->escape((string) $record['description']);
            }$lines[] = 'STATUS:'.strtoupper((string) ($record['status'] ?? 'confirmed'));
            $lines[] = 'X-RIMBA-RECORD-TYPE:'.($target === 'holidays' ? 'holiday' : 'workday');
            if (filled($record['team'] ?? null)) {
                $lines[] = 'X-RIMBA-TEAM:'.$record['team'];
            }if (filled($record['shift_code'] ?? null)) {
                $lines[] = 'X-RIMBA-SHIFT-CODE:'.$record['shift_code'];
            }if (filled($record['shift_name'] ?? null)) {
                $lines[] = 'X-RIMBA-SHIFT-NAME:'.$this->escape($record['shift_name']);
            }$lines[] = 'END:VEVENT';
        }$lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map([$this, 'fold'], $lines))."\r\n";
    }

    private function normalizeImported(array $e, string $target, string $tz): array
    {
        $base = ['uid' => $e['uid'] ?? Str::uuid().'@rimba', 'title' => $e['title'] ?? 'Untitled', 'description' => $e['description'] ?? null, 'status' => $e['status'] ?? 'confirmed', 'color' => $e['color'] ?? null, 'attributes' => $e['attributes'] ?? []];
        if ($target === 'holidays') {
            return $base + ['type' => $e['categories'] ?? 'Holiday', 'date' => $e['date'] ?? substr((string) ($e['starts_at'] ?? ''), 0, 10), 'is_all_day' => true];
        }

        return $base + ['date' => $e['date'] ?? substr((string) ($e['starts_at'] ?? ''), 0, 10), 'team' => $e['team'] ?? null, 'shift_code' => $e['shift_code'] ?? null, 'shift_name' => $e['shift_name'] ?? ($e['title'] ?? 'Shift'), 'starts_at' => $e['starts_at'] ?? null, 'ends_at' => $e['ends_at'] ?? null, 'timezone' => $e['timezone'] ?? $tz];
    }

    private function putDate(array &$event, string $which, string $value, array $params, string $tz): void
    {
        $isDate = ($params['VALUE'] ?? null) === 'DATE' || preg_match('/^\d{8}$/', $value);
        if ($isDate) {
            $date = CarbonImmutable::createFromFormat('Ymd', substr($value, 0, 8), $tz)->format('Y-m-d');
            $event[$which === 'start' ? 'date' : 'end_date'] = $date;
            $event['is_all_day'] = true;

            return;
        }$zone = $params['TZID'] ?? $tz;
        $format = str_ends_with($value, 'Z') ? 'Ymd\THis\Z' : 'Ymd\THis';
        $date = CarbonImmutable::createFromFormat($format, $value, str_ends_with($value, 'Z') ? 'UTC' : $zone);
        $event[$which.'_at'] = $date->toIso8601String();
        $event['timezone'] = $zone;
    }

    private function escape(string $s): string
    {
        return str_replace(['\\', ';', ',', "\r\n", "\n"], ['\\\\', '\\;', '\\,', '\\n', '\\n'], $s);
    }

    private function unescape(string $s): string
    {
        return str_replace(['\\n', '\\N', '\\,', '\\;', '\\\\'], ["\n", "\n", ',', ';', '\\'], $s);
    }

    private function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }$out = '';
        while (strlen($line) > 75) {
            $cut = 75;
            while ($cut > 0 && (ord($line[$cut]) & 0xC0) === 0x80) {
                $cut--;
            }$out .= substr($line, 0, $cut)."\r\n ";
            $line = substr($line, $cut);
        }

        return $out.$line;
    }
}
