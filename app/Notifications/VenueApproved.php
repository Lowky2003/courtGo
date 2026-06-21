<?php

namespace App\Notifications;

use App\Models\Venue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Emails the owner that their venue has been approved. */
class VenueApproved extends Notification
{
    use Queueable;

    public function __construct(public Venue $venue)
    {
    }

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your venue '.$this->venue->name.' is approved')
            ->greeting('Good news, '.$notifiable->name.'!')
            ->line('Your venue "'.$this->venue->name.'" has been approved on CourtGo.')
            ->line('To start taking bookings, subscribe this venue and connect your bank in Billing.')
            ->action('Go to Billing', route('owner.billing'))
            ->line('Thanks for listing with CourtGo!');
    }
}
