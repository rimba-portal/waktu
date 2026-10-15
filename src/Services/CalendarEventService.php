<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Rimba\Time\Enums\EventType;

final class CalendarEventService
{
    public function __construct(
        private TimeJsonRepository $timeJsonRepository,
        private ShiftGeneratorService $shiftGeneratorService,
    ) {}

    public function forUser(
        Authenticatable $user,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $holidays = collect($this->timeJsonRepository->all('holidays'))
            ->filter(fn (array $holiday): bool => ($holiday['date'] ?? '') >= $from->format('Y-m-d')
                && ($holiday['date'] ?? '') <= $to->format('Y-m-d')
            )
            ->map(fn (array $holiday): array => $this->holidayEvent($holiday))
            ->values()
            ->all();

        $role = $this->shiftGeneratorService->roleFor($user);

        $shifts = $role
            ? $this->shiftGeneratorService->eventsForRole($role, $from, $to)
            : [];

        return [
            ...$holidays,
            ...$shifts,
        ];
    }

    private function holidayEvent(array $holiday): array
    {
        $type = EventType::tryFrom((string) ($holiday['type'] ?? ''))
            ?? EventType::Others;

        $palette = $type->getColor();

        return [
            'id' => (string) ($holiday['uid'] ?? $holiday['date']),
            'title' => (string) ($holiday['title'] ?? $type->getLabel()),
            'start' => (string) $holiday['date'],
            'allDay' => true,
            'backgroundColor' => $this->shade($palette, 50),
            'borderColor' => $this->shade($palette, 200),
            'textColor' => $this->shade($palette, 700),
            'extendedProps' => [
                'kind' => 'holiday',
                'type' => $type->value,
                'description' => $holiday['description'] ?? null,
            ],
        ];
    }

    private function shade(string|array|null $color, int $shade): ?string
    {
        if (is_array($color)) {
            return $color[$shade] ?? $color[500] ?? null;
        }

        return $color;
    }
}
