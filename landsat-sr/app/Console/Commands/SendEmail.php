<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Observation;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SatelliteApproaching;

class SendEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Observation::whereRaw("date(overpass_time) = date('now', '+' || lead_time || ' days')")
            ->get()
            ->each(function ($observation) {
                if (! is_array($observation->recipients)) {
                    return;
                }

                foreach ($observation->recipients as $recipient) {
                    Notification::route('mail', $recipient)
                        ->notify(new SatelliteApproaching($observation));
                }
            });
    }
}
