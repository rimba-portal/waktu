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

abstract class ManageJsonCollection extends Page
{
    protected string $view = 'bites::manage-json-collection';

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
                    ->hiddenLabel()
                    ->schema($this->recordSchema())
                    ->columns(2)
                    ->collapsible()
                    ->cloneable()
                    ->reorderable()
                    // CHANGE THIS LINE: Use $state instead of $s
                    ->itemLabel(fn (?array $state): ?string => $state['title'] ?? $state['name'] ?? $state['uid'] ?? $state['code'] ?? null)
                    ->defaultItems(0),
            ]);
    }

    public function save(TimeJsonRepository $repo): void
    {
        $repo->replace(static::collection(), $this->data['records'] ?? []);
        Notification::make()
            ->title('Saved')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->importJson(),
            $this->exportJson(),
            ...($this->supportsIcs() ? [
                $this->importIcs(),
                $this->exportIcs(),
            ] : []),
        ];
    }

    protected function supportsIcs(): bool
    {
        return false;
    }

    private function importJson(): Action
    {
        return Action::make('importJson')
            ->label('Import JSON')
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('file')
                    ->disk('local')
                    ->directory('waktu-imports')
                    ->acceptedFileTypes(['application/json', 'text/json', 'text/plain'])
                    ->required(),
                Select::make('strategy')
                    ->options([
                        'update' => 'Update',
                        'append' => 'Append',
                        'replace_all' => 'Replace all',
                    ])
                    ->default('update')
                    ->required(),
            ])
            ->action(function (array $data, TimeJsonRepository $repo, CalendarConverterService $c): void {
                $content = Storage::disk('local')->get($data['file']);
                $rows = $c->jsonToArray($content);
                $repo->merge(static::collection(), $rows, $data['strategy']);
                Storage::disk('local')
                    ->delete($data['file']);
                $this->form->fill(['records' => $repo->all(static::collection())]);
            });
    }

    private function importIcs(): Action
    {
        return Action::make('importIcs')
            ->label('Import ICS')
            ->schema([
                FileUpload::make('file')
                    ->disk('local')
                    ->directory('waktu-imports')
                    ->acceptedFileTypes(['text/calendar', 'text/plain'])
                    ->required(),
            ])
            ->action(function (array $data, TimeJsonRepository $repo, CalendarConverterService $c): void {
                $rows = $c->holidaysFromIcs(Storage::disk('local')->get($data['file']));
                $repo->merge('holidays', $rows, 'update');
                Storage::disk('local')
                    ->delete($data['file']);
                $this->form->fill(['records' => $repo->all('holidays')]);
            });
    }

    private function exportJson(): Action
    {
        return Action::make('exportJson')
            ->label('Export JSON')
            ->action(fn (TimeJsonRepository $r): StreamedResponse => response()
                ->streamDownload(fn (): int => print (json_encode($r->all(static::collection()), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), static::collection().'.json', ['Content-Type' => 'application/json']));
    }

    private function exportIcs(): Action
    {
        return Action::make('exportIcs')
            ->label('Export ICS')
            ->action(function (TimeJsonRepository $r, CalendarConverterService $c): StreamedResponse {
                $ics = $c->holidaysToIcs($r->all('holidays'));

                return response()
                    ->streamDownload(fn (): int => print ($ics), 'holidays.ics', ['Content-Type' => 'text/calendar; charset=utf-8']);
            });
    }
}
