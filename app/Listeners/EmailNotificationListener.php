<?php

namespace App\Listeners;

use App\Events\EmailNotificationDispatchedEvent;
use App\Mail\NotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class EmailNotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(EmailNotificationDispatchedEvent $event): void
    {
        Mail::to($event->user->email)->send(new NotificationMail(
            title: $event->title,
            content: $event->content,
        ));
    }
}
