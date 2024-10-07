<?php

namespace App\Filament\Resources\ObservationResource\Pages;

use App\Filament\Resources\ObservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Pages\Actions\ButtonAction;

class ViewObservation extends ViewRecord
{
    protected static string $resource = ObservationResource::class;

    public function getTitle(): string
    {
        return $this->record->title;
    }

    public function getSubheading(): string
    {
        return $this->record->created_at->diffForHumans();
    }

    protected function getHeaderActions(): array
    {
        return [
            ButtonAction::make('analyseButton')
                ->label('Analyse')
                ->url(fn(): string => route('filament.admin.resources.observations.analysis', $this->record))
                ->color('primary')
                ->icon('heroicon-o-beaker')
                ->size('lg')
        ];
    }
}
