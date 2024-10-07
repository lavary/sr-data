<?php

namespace App\Filament\Resources\ObservationResource\Pages;

use App\Filament\Resources\ObservationResource;
use Filament\Actions;
use Filament\Forms;
use App\Enums\CommunicationChannel;
use App\Enums\Satellite;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Wizard\Step;

class CreateObservation extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static ?string $title = '🛰️ Create observation';

    protected static string $resource = ObservationResource::class;

    protected function getSteps(): array
    {
        return [
            Step::make('Location')
                ->description('Pick the location')
                ->schema([
                    Forms\Components\ViewField::make('place')
                        ->view('filament.forms.components.place')
                        ->required(),
                    Forms\Components\Hidden::make('latitude'),
                    Forms\Components\Hidden::make('longitude'),
                    Forms\Components\Hidden::make('overpass_time'),
                ]),

            Step::make('Details')
                ->description('Pick the location')
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TagsInput::make('tags')
                        ->default(['Surface Reflectance', 'Landsat']),

                    Forms\Components\Textarea::make('description')
                        ->rows(10),

                    Forms\Components\Toggle::make('only_sunlit')
                        ->offIcon('heroicon-o-sun')
                        ->onIcon('heroicon-s-sun'),

                    Forms\Components\TextInput::make('cloud_cover')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->prefixIcon('heroicon-o-cloud'),

                    Forms\Components\ToggleButtons::make('satellite')
                        ->options(Satellite::class)
                        ->grouped()
                ]),

            Step::make('Schedule')
                ->description('Define the lead time')
                ->schema([
                    Forms\Components\Select::make('channel')
                        ->label('Communication Channel')
                        ->options(CommunicationChannel::class)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('lead_time')
                        ->label('Lead time')
                        ->required()
                        ->options([
                            1 => 'One day',
                            2 => 'Two days',
                            3 => 'Three days',
                            4 => 'Four days',
                            5 => 'Five days',
                        ])
                        ->dehydrateStateUsing(fn(string $state): string => ucwords($state)),

                    Repeater::make('recipients')
                        ->simple(
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->placeholder('Email address')
                                ->required(),
                        )
                        ->distinct()
                        ->grid(2)
                        ->maxItems(5),
                ])
        ];
    }
}
