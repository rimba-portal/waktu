<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

final class CalendarEventService
{
    public function __construct(private TimeJsonRepository $timeJsonRepository, private ShiftGeneratorService $shiftGeneratorService) {}

    public function forUser(Authenticatable $user, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $holidays = collect($this->timeJsonRepository->all('holidays'))
            ->filter(fn ($h): bool => ($h['date'] ?? '') >= $from->format('Y-m-d') && ($h['date'] ?? '') <= $to->format('Y-m-d'))
            ->map(fn ($h): array => [
                'id' => $h['uid'],
                'title' => $h['title'],
                'start' => $h['date'],
                'allDay' => true,
                'color' => $h['color'] ?? '#f97316',
                'extendedProps' => [
                    'kind' => 'holiday',
                    'type' => $h['type'] ?? null,
                ],
            ])
            ->values()
            ->all();
        $role = $this->shiftGeneratorService->roleFor($user);
        $shifts = $role ? $this->shiftGeneratorService->eventsForRole($role, $from, $to) : [];

        return [...$holidays, ...$shifts];
    }
}
