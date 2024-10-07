<?php

namespace App\Filament\Resources\ObservationResource\Pages;

use App\Enums\AcquisitionStatus;
use App\Enums\Satellite;
use App\Enums\Band;
use App\Filament\Resources\ObservationResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\ViewEntry;
use Filament\Pages\Dashboard\Actions\FilterAction;
use Filament\Pages\Dashboard\Concerns\HasFiltersAction;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Infolists\Components\Map;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Components\KeyValueEntry;
use Carbon\Carbon;

class ViewObservationAnalysis extends ViewRecord
{
    use HasFiltersAction, InteractsWithPageFilters;

    protected static string $resource = ObservationResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Map::make('map')->state(function (Model $record): string {
                    if (! isset($this->filters['start_date'])) {
                        if ($record->status === AcquisitionStatus::READY) {
                            $this->filters['start_date'] = $record->overpass_time->toDateString();
                        } else {
                            $this->filters['start_date'] = Carbon::today()->subMonth();
                            $this->filters['end_date'] = Carbon::today();
                        }
                    }

                    return http_build_query([
                        ...$this->filters,
                        'latitude' => $record->latitude,
                        'longitude' => $record->longitude,
                    ]);
                }),

                KeyValueEntry::make('')
                    ->state(function (Model $record): array {
                        $pixel = $record->metadata['pixel'];

                        if (! $pixel) {
                            return [];
                        }

                        $multiplier = 0.0000275;
                        $add = -0.2;

                        # Apply the scaling factors
                        return [
                            'Band 1 (Coastal)' => $pixel['SR_B1'] * $multiplier + $add,
                            'Band 2 (Blue)' => $pixel['SR_B2'] * $multiplier + $add,
                            'Band 3 (Green)' => $pixel['SR_B3'] * $multiplier + $add,
                            'Band 4 (Red)' => $pixel['SR_B4'] * $multiplier + $add,
                            'Band 5 (NIR)' => $pixel['SR_B5'] * $multiplier + $add,
                            'Band 6 (SWIR 1)' => $pixel['SR_B6'] * $multiplier + $add,
                            'Band 7 (SWIR 2)' => $pixel['SR_B7'] * $multiplier + $add,
                        ];
                    })

            ]);
    }

    public function getTitle(): string
    {
        return 'Result analysis';
    }

    public function getSubheading(): string
    {
        return $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            FilterAction::make()
                ->form([
                    DatePicker::make('start_date')
                        ->helperText('You can also query historical data if an acquisition had taken place on that date.'),
                    Select::make('satellite')
                        ->options(Satellite::class),
                    TextInput::make('cloud_cover')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(100)
                        ->prefixIcon('heroicon-o-cloud')
                        ->helperText('from 1 to 100'),
                ]),
        ];
    }
}
