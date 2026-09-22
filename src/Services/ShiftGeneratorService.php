<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;
use Illuminate\Contracts\Auth\Authenticatable;
use InvalidArgumentException;

final class ShiftGeneratorService
{
    public function __construct(private TimeJsonRepository $timeJsonRepository) {}

    public function roleFor(Authenticatable $user): ?string
    {
        $prefix = (string) config('bites.time.role_prefix', 'shift_code.');
        $names = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();

        return $names->first(fn (string $name): bool => str_starts_with($name, $prefix));
    }

    public function eventsForRole(string $role, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $prefix = (string) config('bites.time.role_prefix', 'shift_code.');
        $code = str_starts_with($role, $prefix) ? substr($role, strlen($prefix)) : $role;
        $definition = collect($this->timeJsonRepository->all('shift-definitions'))->firstWhere('code', $code);
        if (! $definition) {
            throw new InvalidArgumentException("Unknown shift definition: {$code}");
        }

        $holidays = collect($this->timeJsonRepository->all('holidays'))
            ->keyBy('date');
        $overrides = collect($this->timeJsonRepository->all('overrides'))
            ->filter(fn ($o): bool => ($o['role'] ?? null) === $role)
            ->keyBy('date');
        $events = [];
        foreach (CarbonPeriod::create($from->startOfDay(), $to->startOfDay()) as $day) {
            $date = CarbonImmutable::instance($day);
            $key = $date->format('Y-m-d');
            $override = $overrides->get($key);
            if ($override) {
                $event = $this->overrideEvent($override, $definition, $date, $role);
                if ($event) {
                    $events[] = $event;
                }

                continue;
            }

            if (($definition['holiday_observed'] ?? false) && $holidays->has($key)) {
                continue;
            }

            $event = ($definition['type'] ?? 'fixed') === 'fixed' ? $this->fixedEvent($definition, $date, $role) : $this->cycleEvent($definition, $date, $role);
            if ($event) {
                $events[] = $event;
            }
        }

        return $events;
    }

    private function fixedEvent(array $d, CarbonImmutable $date, string $role): ?array
    {
        if (! in_array(strtoupper($date->format('D')), $d['days'] ?? [], true)) {
            return null;
        }

        return $this->timedEvent(
            $d['name'],
            $date,
            $d['start_time'],
            $d['end_time'],
            $d['color'] ?? '#3b82f6',
            $role,
            $d['code']
        );
    }

    private function cycleEvent(array $d, CarbonImmutable $date, string $role): ?array
    {
        $anchor = CarbonImmutable::parse($d['anchor_date'], config('bites.time.timezone'));
        $cycle = $d['cycle'] ?? [];
        if ($cycle === []) {
            return null;
        }

        $index = ($anchor->diffInDays($date, false) + (int) ($d['offset'] ?? 0)) % count($cycle);
        if ($index < 0) {
            $index += count($cycle);
        }

        $code = $cycle[$index];
        $shift = collect($d['shifts'] ?? [])->firstWhere('code', $code);
        if (! $shift || ($shift['working'] ?? true) === false) {
            return null;
        }

        return $this->timedEvent(
            $shift['name'] ?? $code,
            $date,
            $shift['start_time'],
            $shift['end_time'],
            $shift['color'] ?? ($d['color'] ?? '#3b82f6'),
            $role,
            $code
        );
    }

    private function overrideEvent(array $o, array $definition, CarbonImmutable $date, string $role): ?array
    {
        if (in_array($o['action'] ?? 'skip', ['skip', 'holiday', 'rest'], true)) {
            return null;
        }

        $code = $o['shift_code'] ?? null;
        $shift = collect($definition['shifts'] ?? [])->firstWhere('code', $code);
        $start = $o['start_time'] ?? $shift['start_time'] ?? $definition['start_time'] ?? null;
        $end = $o['end_time'] ?? $shift['end_time'] ?? $definition['end_time'] ?? null;
        if (! $start || ! $end) {
            return null;
        }

        return $this->timedEvent(
            $o['title'] ?? $shift['name'] ?? $definition['name'] ?? 'Shift',
            $date,
            $start,
            $end,
            $o['color'] ?? $shift['color'] ?? $definition['color'] ?? '#3b82f6',
            $role,
            $code ?? $definition['code']
        );
    }

    private function timedEvent(string $title, CarbonImmutable $date, string $start, string $end, string $color, string $role, string $code): array
    {
        $tz = (string) config('bites.time.timezone');
        $s = $date->setTimezone($tz)->setTimeFromTimeString($start);
        $e = $date->setTimezone($tz)->setTimeFromTimeString($end);
        if ($e->lessThanOrEqualTo($s)) {
            $e = $e->addDay();
        }

        return [
            'id' => $role.'-'.$date->format('Ymd'),
            'title' => $title,
            'start' => $s->toIso8601String(),
            'end' => $e->toIso8601String(),
            'allDay' => false,
            'color' => $color,
            'extendedProps' => [
                'kind' => 'shift',
                'role' => $role,
                'shift_code' => $code,
            ],
        ];
    }
}
