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
        $timezone = (string) config('bites.time.timezone', 'UTC');

        foreach ($lines as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }

            [$left, $value] = explode(':', $line, 2);
            $parts = explode(';', $left);
            $key = strtoupper(array_shift($parts));
            $parameters = [];

            foreach ($parts as $part) {
                if (! str_contains($part, '=')) {
                    continue;
                }

                [$parameterKey, $parameterValue] = explode('=', $part, 2);
                $parameters[strtoupper($parameterKey)] = $parameterValue;
            }

            if ($key === 'X-WR-TIMEZONE') {
                $timezone = $value;

                continue;
            }

            if ($key === 'BEGIN' && $value === 'VEVENT') {
                $event = ['attributes' => []];

                continue;
            }

            if ($key === 'END' && $value === 'VEVENT' && is_array($event)) {
                $event['uid'] ??= Str::uuid().'@rimba';
                $event['title'] ??= 'Holiday';
                $event['type'] ??= 'Paid Public Holiday';
                $event['status'] ??= 'confirmed';

                $rows[] = $event;
                $event = null;

                continue;
            }

            if (! is_array($event)) {
                continue;
            }

            match ($key) {
                'UID' => $event['uid'] = $value,
                'SUMMARY' => $event['title'] = $this->unescape($value),
                'DESCRIPTION' => $event['description'] = $this->unescape($value),
                'CATEGORIES' => $event['type'] = $this->unescape($value),
                'STATUS' => $event['status'] = strtolower($value),
                'DTSTART' => $event['date'] = $this->parseIcsDate(
                    $value,
                    $parameters,
                    $timezone,
                ),
                default => $event['attributes'][$key] = $value,
            };
        }

        return $rows;
    }

    public function holidaysToIcs(array $rows): string
    {
        $timezone = (string) config('bites.time.timezone', 'UTC');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Rimba Waktu//EN',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:Rimba Holidays',
            'X-WR-TIMEZONE:'.$timezone,
        ];

        foreach ($rows as $row) {
            $date = CarbonImmutable::parse($row['date'], $timezone);

            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:'.($row['uid'] ?? Str::uuid().'@rimba');
            $lines[] = 'DTSTAMP:'.gmdate('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:'.$date->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$date->addDay()->format('Ymd');
            $lines[] = 'SUMMARY:'.$this->escape((string) ($row['title'] ?? 'Holiday'));
            $lines[] = 'CATEGORIES:'.$this->escape((string) ($row['type'] ?? 'Others'));
            $lines[] = 'STATUS:'.strtoupper((string) ($row['status'] ?? 'confirmed'));

            if (filled($row['description'] ?? null)) {
                $lines[] = 'DESCRIPTION:'.$this->escape((string) $row['description']);
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    private function parseIcsDate(
        string $value,
        array $parameters,
        string $timezone,
    ): string {
        $isDate = ($parameters['VALUE'] ?? null) === 'DATE'
            || preg_match('/^\d{8}$/', $value) === 1;

        if ($isDate) {
            return CarbonImmutable::createFromFormat(
                'Ymd',
                substr($value, 0, 8),
                $timezone,
            )->format('Y-m-d');
        }

        return CarbonImmutable::parse(
            $value,
            $parameters['TZID'] ?? $timezone,
        )->format('Y-m-d');
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\n"],
            ['\\\\', '\\;', '\\,', '\\n'],
            $value,
        );
    }

    private function unescape(string $value): string
    {
        return str_replace(
            ['\\n', '\\,', '\\;', '\\\\'],
            ["\n", ',', ';', '\\'],
            $value,
        );
    }
}
