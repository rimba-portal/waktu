<?php

declare(strict_types=1);

namespace Rimba\Time\Http\UI\Admin\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;
use Rimba\Time\Services\CalendarConverterService;
use Rimba\Time\Services\TimeJsonRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class ManageJsonCalendar extends Page
{
    protected string $view = 'bites::manage-json-calendar';

    public ?array $data = [];

    abstract protected static function collection(): string;

    abstract protected function recordSchema(): array;

    public function mount(TimeJsonRepository $repo): void
    {
        $this->form->fill(['records' => $repo->all(static::collection())]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Repeater::make('records')
                    ->label('')
                    ->schema($this->recordSchema())
                    ->columns(2)
                    ->collapsible()
                    ->cloneable()
                    ->reorderable()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? $state['shift_name'] ?? $state['uid'] ?? null)
                    ->defaultItems(0)
                    ->addActionLabel('Add record'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
            $this->exportJsonAction(),
            $this->exportIcsAction(),
        ];
    }

    public function save(TimeJsonRepository $repo): void
    {
        $repo->replace(static::collection(), $this->data['records'] ?? []);
        Notification::make()->title('Calendar data saved')->success()->send();
    }

    private function importAction(): Action
    {
        return Action::make('import')
            ->label('Import')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('file')
                    ->disk('local')
                    ->directory('waktu-imports')
                    ->acceptedFileTypes(['application/json', 'text/json', 'text/calendar', 'application/ics', 'text/plain'])
                    ->required(),
                Select::make('format')
                    ->options([
                        'auto' => 'Auto detect',
                        'json' => 'JSON',
                        'ics' => 'ICS',
                    ])
                    ->default('auto')
                    ->required(),
                Select::make('strategy')
                    ->options([
                        'update' => 'Update by UID',
                        'append' => 'Append new UIDs only',
                        'replace_range' => 'Replace imported date range',
                        'replace_all' => 'Replace all',
                    ])
                    ->default('update')
                    ->required(),
            ])
            ->action(function (array $data, TimeJsonRepository $repo, CalendarConverterService $converter): void {
                $path = Storage::disk('local')->path($data['file']);
                $content = file_get_contents($path);
                $format = $data['format'] === 'auto' ? (str_ends_with(strtolower($data['file']), '.ics') ? 'ics' : 'json') : $data['format'];
                $rows = $format === 'ics' ? $converter->icsToArray($content, static::collection()) : $converter->jsonToArray($content);
                $repo->merge(static::collection(), $rows, $data['strategy']);
                Storage::disk('local')
                    ->delete($data['file']);
                $this->form->fill(['records' => $repo->all(static::collection())]);
                Notification::make()
                    ->title(count($rows).' records imported')->success()->send();
            });
    }

    private function exportJsonAction(): Action
    {
        return Action::make('exportJson')
            ->label('Export JSON')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn (TimeJsonRepository $repo): StreamedResponse => response()->streamDownload(fn (): int => print (json_encode($repo->all(static::collection()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), static::collection().'-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']));
    }

    private function exportIcsAction(): Action
    {
        return Action::make('exportIcs')
            ->label('Export ICS')
            ->icon('heroicon-o-calendar-days')
            ->action(function (TimeJsonRepository $repo, CalendarConverterService $converter): StreamedResponse {
                $ics = $converter->arrayToIcs($repo->all(static::collection()), ucfirst(static::collection()), static::collection());

                return response()->streamDownload(fn (): int => print ($ics), static::collection().'-'.now()->format('Y-m-d').'.ics', ['Content-Type' => 'text/calendar; charset=utf-8']);
            });
    }
}
