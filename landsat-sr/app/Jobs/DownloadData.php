<?php

namespace App\Jobs;

use App\Enums\AcquisitionStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\Observation;
use App\Notifications\DataReady;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class DownloadData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Observation $observation
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startDate = $this->observation->overpass_time;
        $endDate = '2024-09-07';

        $response = Http::get('http://127.0.0.1:8000/acquire', [
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate,
            'lat' => $this->observation->latitude,
            'lng' => $this->observation->longitude,
            'satellite' => $this->observation->satellite->value,
        ]);

        if ($response->json()) {
            $this->observation->metadata = $response->json()['data'];
            $this->observation->status = AcquisitionStatus::READY;
            $this->observation->save();

            if (! is_array($this->observation->recipients)) {
                return;
            }

            foreach ($this->observation->recipients as $recipient) {
                Notification::route('mail', $recipient)
                    ->notify(new DataReady($this->observation));
            }
        }
    }
}
