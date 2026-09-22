<?php

declare(strict_types=1);

namespace Rimba\Time\Services;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final class TimeJsonRepository
{
    public function all(string $collection): array
    {
        $path = $this->path($collection);
        $disk = $this->disk();
        if (! $disk->exists($path)) {
            $this->replace($collection, []);

            return [];
        }

        try {
            $rows = json_decode($disk->get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException("Invalid JSON in {$path}: {$jsonException->getMessage()}", 0, $jsonException);
        }

        if (! is_array($rows)) {
            throw new RuntimeException("{$path} must contain a JSON array.");
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    public function replace(string $collection, array $records): void
    {
        $records = array_values(array_map(fn (array $r): array => $this->normalize($r), $records));
        $this->assertUniqueUids($records);
        $json = json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
        $disk = $this->disk();
        $path = $this->path($collection);
        $tmp = $path.'.tmp-'.Str::uuid();
        if (! $disk->put($tmp, $json)) {
            throw new RuntimeException("Unable to write {$tmp}.");
        }

        if ($disk->exists($path)) {
            $disk->delete($path);
        }

        if (! $disk->move($tmp, $path)) {
            throw new RuntimeException("Unable to replace {$path}.");
        }
    }

    public function merge(string $collection, array $incoming, string $strategy = 'update'): array
    {
        $existing = $this->all($collection);
        $incoming = array_map(fn (array $r): array => $this->normalize($r), $incoming);
        if ($strategy === 'replace_all') {
            $this->replace($collection, $incoming);

            return $incoming;
        }

        if ($strategy === 'replace_range' && $incoming !== []) {
            $dates = array_filter(array_map(fn (array $r) => $r['date'] ?? substr((string) ($r['starts_at'] ?? ''), 0, 10), $incoming));
            if ($dates !== []) {
                $min = min($dates);
                $max = max($dates);
                $existing = array_values(array_filter($existing, function (array $r) use ($min, $max): bool {
                    $d = $r['date'] ?? substr((string) ($r['starts_at'] ?? ''), 0, 10);

                    return ! $d || $d < $min || $d > $max;
                }));
            }
        }

        $by = [];
        foreach ($existing as $r) {
            $by[$r['uid']] = $r;
        }

        foreach ($incoming as $r) {
            if ($strategy === 'append' && isset($by[$r['uid']])) {
                continue;
            }$by[$r['uid']] = $r;
        }

        $merged = array_values($by);
        usort($merged, fn (array $a, array $b): int => strcmp((string) ($a['date'] ?? $a['starts_at'] ?? ''), (string) ($b['date'] ?? $b['starts_at'] ?? '')));
        $this->replace($collection, $merged);

        return $merged;
    }

    private function normalize(array $record): array
    {
        $record['uid'] = (string) ($record['uid'] ?? Str::uuid().'@rimba');
        $record['attributes'] = is_array($record['attributes'] ?? null) ? $record['attributes'] : [];

        return $record;
    }

    private function assertUniqueUids(array $records): void
    {
        $uids = array_column($records, 'uid');
        if (count($uids) !== count(array_unique($uids))) {
            throw new RuntimeException('Duplicate UID found.');
        }
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk((string) config('bites.disk', 'public'));
    }

    private function path(string $collection): string
    {
        if (! in_array($collection, ['holidays', 'workdays'], true)) {
            throw new RuntimeException('Unsupported time collection.');
        }

        return trim((string) config('bites.directory', 'time'), '/').'/'.$collection.'.json';
    }
}
