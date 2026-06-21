<?php

namespace App\Notifications;

use App\Models\Venue;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Emails the owner that their venue was not approved, with the admin's reason. */
class VenueRejected extends Notification
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
            ->subject('Your venue '.$this->venue->name.' needs changes')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your venue "'.$this->venue->name.'" was not approved yet.')
            ->line('Reason: '.$this->venue->rejection_reason)
            ->line('Please update the relevant verification document and it will be reviewed again.')
            ->action('Update my venue', route('owner.venues.profile', $this->venue))
            ->line('Once you re-upload, your venue goes back into the review queue automatically.');
    }
}
