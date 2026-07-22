<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Arr;
use InvalidArgumentException;

class ShiftPattern
{
    protected string $patternKey;

    protected Carbon $anchorDate;

    protected string $timezone;

    protected array $teams;

    protected array $segments;

    protected int $cycleLength;

    public function __construct(
        string $patternKey,
        Carbon $anchorDate,
        string $timezone,
        array $teams,
        array $segments,
        int $cycleLength,

    ) {
        $this->patternKey = $patternKey;
        $this->anchorDate = $anchorDate->copy()->startOfDay();
        $this->timezone = $timezone;
        $this->teams = $teams;
        $this->segments = $segments;
        $this->cycleLength = $cycleLength;
        $this->anchorDate->setTimezone($this->timezone);
    }

    public static function fromConfig(string $patternKey): self
    {
        $patterns = config('shift_pattern.patterns', []);
        $patternKey = $patternKey ?: config('shift_pattern.default', 'WXYZ');

        if (! isset($patterns[$patternKey])) {
            throw new InvalidArgumentException('Unknown shift pattern: '.$patternKey);
        }

        $cfg = $patterns[$patternKey];

        return new self(
            patternKey: $patternKey,
            anchorDate: Carbon::parse($cfg['anchor_date']),
            timezone: $cfg['timezone'] ?? config('app.timezone', 'Asia/Kuala_Lumpur'),
            teams: $cfg['teams'] ?? [],
            segments: $cfg['segments'] ?? [],
            cycleLength: $cfg['cycle_length'] ?? 24,
        );
    }

    public function getPatternKey(): string
    {
        return $this->patternKey;
    }

    protected function getSegmentByCode(string $code): ?array
    {
        foreach ($this->segments as $segment) {
            if ($segment['code'] === $code) {
                return $segment;
            }
        }

        return null;
    }

    public function getShiftCode(string $team, Carbon $date): string
    {
        $team = strtoupper($team);
        if (! isset($this->teams[$team])) {
            throw new InvalidArgumentException(sprintf("Unknown team '%s' for pattern '%s'.", $team, $this->patternKey));
        }

        $date = $date->copy()->startOfDay()->setTimezone($this->timezone);

        $d = $this->anchorDate->diffInDays($date, false);
        $offset = (int) Arr::get($this->teams[$team], 'offset', 0);

        $t = ($d + $offset) % $this->cycleLength;
        if ($t < 0) {
            $t += $this->cycleLength;
        }

        $cursor = 0;
        foreach ($this->segments as $segment) {
            $len = (int) $segment['len'];
            if ($t >= $cursor && $t < $cursor + $len) {
                return $segment['code']; // 'M'|'A'|'N'|'R'
            }

            $cursor += $len;
        }

        return 'R'; // If not found, default to rest (won't be emitted as event)
    }

    public function getShiftLabel(string $team, Carbon $date): string
    {
        $seg = $this->getSegmentByCode($this->getShiftCode($team, $date));

        return $seg['label'] ?? 'Rest';
    }

    public function makeEventFor(string $team, Carbon $date): ?array
    {
        $team = strtoupper($team);
        $code = $this->getShiftCode($team, $date);
        $seg = $this->getSegmentByCode($code);
        if (! $seg) {
            return null;
        }

        if ($code === 'R') {
            return null;
        }

        // Timed event
        [$sH, $sM] = explode(':', $seg['start']);
        $endRaw = $seg['end'];                               // e.g., "07:00(+1)"
        $plusOne = str_ends_with($endRaw, '(+1)');
        $endTime = $plusOne ? str_replace('(+1)', '', $endRaw) : $endRaw;
        [$eH, $eM] = explode(':', $endTime);

        $start = $date->copy()->setTime((int) $sH, (int) $sM)->setTimezone($this->timezone);
        $end = $date->copy()->setTime((int) $eH, (int) $eM)->setTimezone($this->timezone);
        if ($plusOne) {
            $end->addDay();
        }

        $color = Arr::get($seg, 'color') ?? Arr::get($this->teams[$team], 'color');

        return [
            'title' => sprintf('%s', $seg['label']),
            'start' => $start->toIso8601String(),
            'end' => $start->copy()->addHour()->toIso8601String(),
            'allDay' => false,
            'color' => $color,
            'classNames' => ['team-'.$team, 'shift-'.$code, 'pat-'.$this->patternKey],
            'extendedProps' => ['shiftCode' => $code, 'team' => $team, 'pattern' => $this->patternKey],
        ];
    }

    public function eventsForTeamInRange(string $team, Carbon $start, Carbon $end): array
    {
        $events = [];
        $carbonPeriod = CarbonPeriod::create(
            $start->copy()->startOfDay()->setTimezone($this->timezone),
            $end->copy()->startOfDay()->setTimezone($this->timezone)
        );
        foreach ($carbonPeriod as $day) {
            if ($event = $this->makeEventFor($team, $day)) {
                $events[] = $event;
            }
        }

        return $events;
    }

    /** Little helper to check if a team belongs to this pattern */
    public function hasTeam(string $team): bool
    {
        return array_key_exists(strtoupper($team), $this->teams);
    }
}
