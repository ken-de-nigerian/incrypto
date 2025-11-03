<?php

namespace App\Listeners;

use App\Events\DatabaseAndEmailNotificationDispatchedEvent;
use App\Mail\NotificationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DatabaseAndEmailNotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DatabaseAndEmailNotificationDispatchedEvent $event): void
    {
        // Create database notification
        $this->createDatabaseNotification($event->user, $event);

        // Send email notification
        $this->sendEmailNotification($event->user, $event);
    }

    private function createDatabaseNotification($user, $event): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => self::class,
            'notifiable_type' => get_class($user),
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => 'broadcast_message',
                'title' => $event->title,
                'content' => $event->content,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sendEmailNotification($user, $event): void
    {
        Mail::to($user->email)->send(new NotificationMail(
            title: $event->title,
            content: $event->content,
        ));
    }
}
