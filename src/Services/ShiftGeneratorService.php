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
        $staff = $user->staff ?? null;
        if (! $staff) {
            return null;
        }

        $prefix = (string) config('bites.time.role_prefix', 'shift_code.');
        $names = method_exists($staff, 'getRoleNames') ? $staff->getRoleNames() : collect();

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
            $date,
            $d['start_time'],
            $d['end_time'],
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
            $date,
            $shift['start_time'],
            $shift['end_time'],
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
            $date,
            $start,
            $end,
            $role,
            $code ?? $definition['code']
        );
    }

    private function timedEvent(CarbonImmutable $date, string $start, string $end, string $role, string $code): array
    {
        $tz = (string) config('bites.time.timezone');
        $s = $date->setTimezone($tz)->setTimeFromTimeString($start);
        $e = $date->setTimezone($tz)->setTimeFromTimeString($end);
        if ($e->lessThanOrEqualTo($s)) {
            $e = $e->addDay();
        }

        return [
            'id' => $role.'-'.$date->format('Ymd'),
            // 'title' => $title,
            'start' => $s->toIso8601String(),
            // 'end' => $e->toIso8601String(),
            'label' => '<svg height="200px" width="200px" version="1.1" id="sun" xmlns="http://w3.org" xmlns:xlink="http://w3.org" viewBox="0 0 1010 1010" enable-background="new 0 0 1010 1010" xml:space="preserve" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g id="sun-sun"> <g id="sun-fill"> <path fill="#ffe8c2" d="M826.1182,505c0,177.335-143.7831,321.1006-321.084,321.1006 C327.7417,826.1006,183.916,682.335,183.916,505c0-177.3184,143.8257-321.1182,321.1182-321.1182 C682.335,183.8818,826.1182,327.6816,826.1182,505z"></path> </g> <g id="sun-line"> <g> <path fill="#37474F" d="M505.0342,833.0557c-180.9146,0-328.0899-147.1671-328.0899-328.0557 c0-180.9233,147.1753-328.0903,328.0899-328.0903c180.8896,0,328.039,147.167,328.039,328.0903 C833.0732,685.8887,685.9238,833.0557,505.0342,833.0557L505.0342,833.0557z M505.0342,212.9785 c-161.0181,0-292.0215,131.0117-292.0215,292.0215c0,160.9922,131.0034,291.9873,292.0215,291.9873 c160.9931,0,291.9697-130.9951,291.9697-291.9873C797.0039,343.9902,666.0273,212.9785,505.0342,212.9785L505.0342,212.9785z"></path> </g> <g> <g> <polygon fill="#37474F" points="1010,523.043 914.5986,523.043 914.5986,486.957 1010,486.957 1010,523.043 "></polygon> </g> <g> <polygon fill="#37474F" points="95.3936,523.043 0,523.043 0,486.957 95.3936,486.957 95.3936,523.043 "></polygon> </g> <g> <polygon fill="#37474F" points="524.999,95.3506 488.9385,95.3506 488.9385,0 524.999,0 524.999,95.3506 "></polygon> </g> <g> <polygon fill="#37474F" points="204.9355,244.8813 138.1455,178.0322 163.6709,152.5234 230.4609,219.373 204.9355,244.8813 "></polygon> </g> <g> <polygon fill="#37474F" points="805.1162,244.8813 779.6074,219.373 846.3711,152.5234 871.8809,178.0322 805.1162,244.8813 "></polygon> </g> <g> <polygon fill="#37474F" points="524.999,1010 488.9385,1010 488.9385,914.6152 524.999,914.6152 524.999,1010 "></polygon> </g> <g> <polygon fill="#37474F" points="163.6709,857.459 138.1455,831.9512 204.9355,765.1016 230.4609,790.6094 163.6709,857.459 "></polygon> </g> <g> <polygon fill="#37474F" points="846.3711,857.459 779.6074,790.6094 805.1162,765.1016 871.8809,831.9512 846.3711,857.459 "></polygon> </g> </g> </g> </g> </g></svg>',
            'allDay' => true,
            'color' => $this->getShiftColor($s),
            'display' => 'background',
            'extendedProps' => [
                'kind' => 'shift',
                'role' => $role,
                'shift_code' => $code,
            ],
        ];
    }

    private function getShiftColor(CarbonImmutable $startTime): string
    {
        $startHour = (int) $startTime->format('G');

        if ($startHour >= 6 && $startHour < 12) {
            return 'success'; // Morning (Standard blue/cyan palette)
        }

        if ($startHour >= 12 && $startHour < 16) {
            return 'warning'; // Afternoon (Standard amber/yellow palette)
        }

        if ($startHour >= 16 && $startHour < 20) {
            return 'danger'; // Evening (Standard red palette)
        }

        return 'gray'; // Night (Standard slate/gray palette)
    }
}
