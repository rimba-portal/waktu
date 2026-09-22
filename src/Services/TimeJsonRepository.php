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
    private const COLLECTIONS = ['holidays', 'shift-definitions', 'overrides'];

    public function all(string $collection): array
    {
        $disk = $this->disk();
        $path = $this->path($collection);
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

    public function replace(string $collection, array $rows): void
    {
        $rows = array_values(array_map(fn (array $r): array => $this->normalize($collection, $r), $rows));
        $key = $collection === 'shift-definitions' ? 'code' : 'uid';
        $values = array_column($rows, $key);
        if (count($values) !== count(array_unique($values))) {
            throw new RuntimeException("Duplicate {$key} in {$collection}.");
        }

        $json = json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL;
        $disk = $this->disk();
        $path = $this->path($collection);
        $tmp = $path.'.tmp-'.Str::uuid();
        if (! $disk->put($tmp, $json)) {
            throw new RuntimeException("Unable to write {$tmp}.");
        }

        if ($disk->exists($path) && ! $disk->delete($path)) {
            $disk->delete($tmp);
            throw new RuntimeException("Unable to replace {$path}.");
        }

        if (! $disk->move($tmp, $path)) {
            throw new RuntimeException("Unable to move temporary file to {$path}.");
        }
    }

    public function merge(string $collection, array $incoming, string $strategy = 'update'): array
    {
        if ($strategy === 'replace_all') {
            $this->replace($collection, $incoming);

            return $incoming;
        }

        $key = $collection === 'shift-definitions' ? 'code' : 'uid';
        $existing = $this->all($collection);
        $map = [];
        foreach ($existing as $row) {
            $map[(string) $row[$key]] = $row;
        }

        foreach ($incoming as $row) {
            $row = $this->normalize($collection, $row);
            $id = (string) $row[$key];
            if ($strategy === 'append' && isset($map[$id])) {
                continue;
            }$map[$id] = $row;
        }

        $rows = array_values($map);
        $this->replace($collection, $rows);

        return $rows;
    }

    private function normalize(string $collection, array $row): array
    {
        if ($collection === 'shift-definitions') {
            $row['code'] = (string) ($row['code'] ?? '');
        } else {
            $row['uid'] = (string) ($row['uid'] ?? Str::uuid().'@rimba');
        }

        $row['attributes'] = is_array($row['attributes'] ?? null) ? $row['attributes'] : [];

        return $row;
    }

    private function disk(): FilesystemAdapter
    {
        return Storage::disk((string) config('bites.time.disk', 'public'));
    }

    private function path(string $collection): string
    {
        if (! in_array($collection, self::COLLECTIONS, true)) {
            throw new RuntimeException("Unsupported collection: {$collection}");
        }

        return trim((string) config('bites.time.directory', 'time'), '/').'/'.$collection.'.json';
    }
}
