<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Team\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Rimba\Time\Services\CalendarConverterService;
use Rimba\Time\Services\TimeJsonRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ManageJsonCollection extends Page
{
    protected string $view = 'bites::manage-json-collection';

    public ?array $data = [];

    abstract protected static function collection(): string;

    public function mount(TimeJsonRepository $repository): void
    {
        $this->refreshRecords($repository);
    }

    public function save(TimeJsonRepository $repository): void
    {
        $repository->replace(
            static::collection(),
            $this->data['records'] ?? [],
        );

        Notification::make()
            ->title('Saved')
            ->success()
            ->send();
    }

    protected function refreshRecords(TimeJsonRepository $repository): void
    {
        $this->form->fill([
            'records' => $repository->all(static::collection()),
        ]);
    }

    protected function supportsIcs(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->importJsonAction(),
            $this->exportJsonAction(),

            ...($this->supportsIcs() ? [
                $this->importIcsAction(),
                $this->exportIcsAction(),
            ] : []),
        ];
    }

    private function importJsonAction(): Action
    {
        return Action::make('importJson')
            ->label('Import JSON')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('file')
                    ->disk('local')
                    ->directory('waktu-imports')
                    ->acceptedFileTypes([
                        'application/json',
                        'text/json',
                        'text/plain',
                    ])
                    ->required(),

                Select::make('strategy')
                    ->options([
                        'update' => 'Update existing',
                        'append' => 'Append new',
                        'replace_all' => 'Replace all',
                    ])
                    ->default('update')
                    ->required(),
            ])
            ->action(function (
                array $data,
                TimeJsonRepository $repository,
                CalendarConverterService $converter,
            ): void {
                $path = $data['file'];

                try {
                    $records = $converter->jsonToArray(
                        Storage::disk('local')->get($path),
                    );

                    $repository->merge(
                        static::collection(),
                        $records,
                        $data['strategy'],
                    );

                    $this->refreshRecords($repository);

                    Notification::make()
                        ->title('JSON imported')
                        ->success()
                        ->send();
                } finally {
                    Storage::disk('local')->delete($path);
                }
            });
    }

    private function exportJsonAction(): Action
    {
        return Action::make('exportJson')
            ->label('Export JSON')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(
                fn (TimeJsonRepository $repository): StreamedResponse => response()->streamDownload(
                    fn (): int => print json_encode(
                        $repository->all(static::collection()),
                        JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_SLASHES
                        | JSON_UNESCAPED_UNICODE
                        | JSON_THROW_ON_ERROR,
                    ),
                    static::collection().'.json',
                    ['Content-Type' => 'application/json; charset=utf-8'],
                ),
            );
    }

    private function importIcsAction(): Action
    {
        return Action::make('importIcs')
            ->label('Import ICS')
            ->icon('heroicon-o-calendar-days')
            ->schema([
                FileUpload::make('file')
                    ->disk('local')
                    ->directory('waktu-imports')
                    ->acceptedFileTypes([
                        'text/calendar',
                        'text/plain',
                    ])
                    ->required(),

                Select::make('strategy')
                    ->options([
                        'update' => 'Update existing',
                        'append' => 'Append new',
                        'replace_all' => 'Replace all',
                    ])
                    ->default('update')
                    ->required(),
            ])
            ->action(function (
                array $data,
                TimeJsonRepository $repository,
                CalendarConverterService $converter,
            ): void {
                $path = $data['file'];

                try {
                    $records = $converter->holidaysFromIcs(
                        Storage::disk('local')->get($path),
                    );

                    $repository->merge(
                        static::collection(),
                        $records,
                        $data['strategy'],
                    );

                    $this->refreshRecords($repository);

                    Notification::make()
                        ->title('ICS imported')
                        ->success()
                        ->send();
                } finally {
                    Storage::disk('local')->delete($path);
                }
            });
    }

    private function exportIcsAction(): Action
    {
        return Action::make('exportIcs')
            ->label('Export ICS')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(function (
                TimeJsonRepository $repository,
                CalendarConverterService $converter,
            ): StreamedResponse {
                $ics = $converter->holidaysToIcs(
                    $repository->all(static::collection()),
                );

                return response()->streamDownload(
                    fn (): int => print $ics,
                    static::collection().'.ics',
                    ['Content-Type' => 'text/calendar; charset=utf-8'],
                );
            });
    }
}
