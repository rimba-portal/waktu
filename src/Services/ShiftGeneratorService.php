<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

final class ShiftGeneratorService
{
    public function __construct(
        private TimeJsonRepository $timeJsonRepository,
    ) {}

    public function roleFor(Authenticatable $user): ?string
    {
        $staff = $user->staff ?? null;

        if (! $staff || ! method_exists($staff, 'getRoleNames')) {
            return null;
        }

        $prefix = $this->rolePrefix();

        return $staff->getRoleNames()
            ->first(fn (string $name): bool => str_starts_with($name, $prefix));
    }

    public function codeFromRole(string $role): string
    {
        $prefix = $this->rolePrefix();

        return str_starts_with($role, $prefix)
            ? substr($role, strlen($prefix))
            : $role;
    }

    public function eventsForRole(
        string $role,
        CarbonImmutable $from,
        CarbonImmutable $to,
    ): array {
        $code = $this->codeFromRole($role);

        $definition = collect($this->timeJsonRepository->all('shift-definitions'))
            ->firstWhere('code', $code);

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Unknown shift definition: {$code}");
        }

        $holidays = collect($this->timeJsonRepository->all('holidays'))
            ->keyBy('date');

        $overrides = collect($this->timeJsonRepository->all('overrides'))
            ->filter(fn (array $override): bool => ($override['role'] ?? null) === $role
            )
            ->keyBy('date');

        $events = [];

        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $day) {
            $date = CarbonImmutable::instance($day);
            $dateKey = $date->format('Y-m-d');
            $override = $overrides->get($dateKey);

            if (is_array($override)) {
                $event = $this->overrideEvent($override, $definition, $date, $role);

                if ($event !== null) {
                    $events[] = $event;
                }

                continue;
            }

            if (($definition['holiday_observed'] ?? false) && $holidays->has($dateKey)) {
                continue;
            }

            $event = match ($definition['type'] ?? 'fixed') {
                'fixed' => $this->fixedEvent($definition, $date, $role),
                'cycle' => $this->cycleEvent($definition, $date, $role),
                default => throw new InvalidArgumentException(
                    "Unsupported shift type for {$code}."
                ),
            };

            if ($event !== null) {
                $events[] = $event;
            }
        }

        return $events;
    }

    private function fixedEvent(
        array $definition,
        CarbonImmutable $date,
        string $role,
    ): ?array {
        $dayCode = strtoupper($date->format('D'));

        if (! in_array($dayCode, $definition['days'] ?? [], true)) {
            return null;
        }

        return $this->timedEvent(
            date: $date,
            start: (string) $definition['start_time'],
            end: (string) $definition['end_time'],
            role: $role,
            code: (string) $definition['code'],
            title: (string) ($definition['name'] ?? $definition['code']),
            color: $definition['color'] ?? null,
        );
    }

    private function cycleEvent(
        array $definition,
        CarbonImmutable $date,
        string $role,
    ): ?array {
        $cycle = $definition['cycle'] ?? [];

        if (! is_array($cycle) || $cycle === []) {
            return null;
        }

        $anchor = CarbonImmutable::parse(
            $definition['anchor_date'],
            $this->timezone(),
        );

        $index = (
            $anchor->diffInDays($date, false)
            + (int) ($definition['offset'] ?? 0)
        ) % count($cycle);

        if ($index < 0) {
            $index += count($cycle);
        }

        $code = (string) $cycle[$index];

        $shift = collect($definition['shifts'] ?? [])
            ->firstWhere('code', $code);

        if (! is_array($shift) || ($shift['working'] ?? true) === false) {
            return null;
        }

        return $this->timedEvent(
            date: $date,
            start: (string) $shift['start_time'],
            end: (string) $shift['end_time'],
            role: $role,
            code: $code,
            title: (string) ($shift['name'] ?? $code),
            color: $shift['color'] ?? $definition['color'] ?? null,
        );
    }

    private function overrideEvent(
        array $override,
        array $definition,
        CarbonImmutable $date,
        string $role,
    ): ?array {
        $action = (string) ($override['action'] ?? 'skip');

        if (in_array($action, ['skip', 'holiday', 'rest'], true)) {
            return null;
        }

        if ($action !== 'force_shift') {
            throw new InvalidArgumentException("Unsupported override action: {$action}");
        }

        $code = (string) ($override['shift_code'] ?? $definition['code']);

        $shift = collect($definition['shifts'] ?? [])
            ->firstWhere('code', $code);

        $start = $override['start_time']
            ?? $shift['start_time']
            ?? $definition['start_time']
            ?? null;

        $end = $override['end_time']
            ?? $shift['end_time']
            ?? $definition['end_time']
            ?? null;

        if (! is_string($start) || ! is_string($end)) {
            return null;
        }

        return $this->timedEvent(
            date: $date,
            start: $start,
            end: $end,
            role: $role,
            code: $code,
            title: (string) (
                $override['title']
                ?? $shift['name']
                ?? $definition['name']
                ?? $code
            ),
            color: $override['color']
                ?? $shift['color']
                ?? $definition['color']
                ?? null,
        );
    }

    private function timedEvent(
        CarbonImmutable $date,
        string $start,
        string $end,
        string $role,
        string $code,
        string $title,
        ?string $color,
    ): array {
        $startAt = $date
            ->setTimezone($this->timezone())
            ->setTimeFromTimeString($start);

        $endAt = $date
            ->setTimezone($this->timezone())
            ->setTimeFromTimeString($end);

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt = $endAt->addDay();
        }

        return [
            'id' => implode('-', [
                $role,
                $date->format('Ymd'),
                $code,
            ]),
            'title' => $title,
            'start' => $startAt->toIso8601String(),
            'end' => $endAt->toIso8601String(),
            'allDay' => false,
            'color' => $color,
            'extendedProps' => [
                'kind' => 'shift',
                'role' => $role,
                'shift_code' => $code,
            ],
        ];
    }

    private function rolePrefix(): string
    {
        return (string) config(
            'bites.time.role_prefix',
            'staff◇shift_code◇',
        );
    }

    private function timezone(): string
    {
        return (string) config(
            'bites.time.timezone',
            'Asia/Kuala_Lumpur',
        );
    }
}
