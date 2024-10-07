<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Observation;


class SatelliteApproaching extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Observation $observation)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('Great news!')
            ->subject("{$this->observation->satellite->getLabel()} is approaching in {$this->observation->lead_time} days")
            ->action('See the observation details', url('/'))
            ->line('Thank you for using Cosmic Radar');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
