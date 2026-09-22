<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

final class CalendarEventService
{
    public function __construct(private TimeJsonRepository $timeJsonRepository) {}

    public function fullCalendarEvents(?string $team = null): array
    {
        $holidays = array_map(
            fn (array $r): array => [
                'id' => $r['uid'],
                'title' => $r['title'],
                'start' => $r['date'],
                'allDay' => true,
                'color' => $r['color'] ?? '#f97316',
                'extendedProps' => ['kind' => 'holiday', 'type' => $r['type'] ?? null],
            ],
            $this->timeJsonRepository->all('holidays')
        );
        $workdays = array_values(
            array_map(
                fn (array $r): array => [
                    'id' => $r['uid'],
                    'title' => $r['title'] ?? trim(($r['team'] ?? '').' '.($r['shift_name'] ?? 'Shift')),
                    'start' => $r['starts_at'] ?? $r['date'],
                    'end' => $r['ends_at'] ?? null,
                    'allDay' => blank($r['starts_at'] ?? null),
                    'color' => $r['color'] ?? '#3b82f6',
                    'extendedProps' => [
                        'kind' => 'workday',
                        'team' => $r['team'] ?? null,
                        'shift_code' => $r['shift_code'] ?? null,
                    ],
                ],
                array_filter($this->timeJsonRepository->all('workdays'), fn (array $r): bool => ! $team || ($r['team'] ?? null) === $team)
            )
        );

        return [...$holidays, ...$workdays];
    }
}
