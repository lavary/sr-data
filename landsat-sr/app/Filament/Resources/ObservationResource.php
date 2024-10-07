<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObservationResource\Pages;
use App\Filament\Resources\ObservationResource\RelationManagers;
use App\Models\Observation;
use App\Enums\AcquisitionStatus;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Components\KeyValueEntry;
use App\Infolists\Components\Map;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;

class ObservationResource extends Resource
{
    protected static ?string $model = Observation::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->columns([
                        'sm' => 3,
                        'xl' => 4,
                        '2xl' => 8,
                    ])
                    ->extraAttributes(['class' => 'divide-x divide-gray-300'])
                    ->schema([
                        TextEntry::make('Coordinates')
                            ->state(function (Model $record): string {
                                return number_format($record->latitude, 4) . ', ' . number_format($record->longitude, 4);
                            })
                            ->icon('heroicon-o-map-pin')
                            ->size(TextEntry\TextEntrySize::Medium)
                            ->weight(FontWeight::SemiBold),

                        TextEntry::make('Satellite')
                            ->state(function (Model $record): string {
                                return $record->satellite->getLabel();
                            })
                            ->size(TextEntry\TextEntrySize::Medium)
                            ->weight(FontWeight::SemiBold)
                            ->icon('heroicon-o-signal'),

                        TextEntry::make('overpass_time')
                            ->since()
                            ->dateTimeTooltip()
                            ->size(TextEntry\TextEntrySize::Medium)
                            ->weight(FontWeight::SemiBold)
                            ->icon('heroicon-o-clock'),

                        TextEntry::make('Acquisition status')
                            ->state(function (Model $record) {
                                return $record->status->getLabel();
                            })
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'Pending' => 'warning',
                                'Ready' => 'success',
                            }),

                        Map::make('')
                            ->state(function (Model $record): string {
                                $filters = [];

                                if ($record->status === AcquisitionStatus::READY) {
                                    $filters['start_date'] = $record->overpass_time->toDateString();
                                } else {
                                    $filters['start_date'] = Carbon::today()->subMonth()->toDateString();
                                    $filters['end_date'] = Carbon::today()->toDateString();
                                }

                                return http_build_query([
                                    ...$filters,
                                    'latitude' => $record->latitude,
                                    'longitude' => $record->longitude,
                                    'zoom' => 7,
                                ]);
                            })
                            ->columnSpanFull(),

                        KeyValueEntry::make('metadata')
                            ->state(function (Model $record) {
                                if (! $record->metadata) {
                                    return [];
                                }

                                return array_filter($record->metadata['scene']['properties'], function ($i) {
                                    return ! is_array($i);
                                });
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('overpass_time')
                    ->label('Scheduled acquisition')
                    ->state(function (Observation $observation) {
                        return $observation->overpass_time->diffForHumans();
                    }),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Ready',
                        'secondary' => 'pending',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObservations::route('/'),
            'create' => Pages\CreateObservation::route('/create'),
            'edit' => Pages\EditObservation::route('/{record}/edit'),
            'view' => Pages\ViewObservation::route('/{record}'),
            'analysis' => Pages\ViewObservationAnalysis::route('/{record}/analysis'),
        ];
    }
}
