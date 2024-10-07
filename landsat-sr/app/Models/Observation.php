<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Enums\Satellite;
use App\Enums\CommunicationChannel;
use App\Enums\AcquisitionStatus;

class Observation extends Model
{
    use HasFactory, HasUuids;

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'metadata' => 'array',
            'recipients' => 'array',
            'satellite' => Satellite::class,
            'status' => AcquisitionStatus::class,
            'overpass_time' => 'datetime',
            'communication_channel' => CommunicationChannel::class
        ];
    }
}
