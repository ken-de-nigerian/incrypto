<?php

namespace App\Listeners;

use App\Events\DatabaseNotificationDispatchedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseNotificationListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(DatabaseNotificationDispatchedEvent $event): void
    {
        DB::table('notifications')->insert([
            'id' => Str::uuid(),
            'type' => self::class,
            'notifiable_type' => get_class($event->user),
            'notifiable_id' => $event->user->id,
            'data' => json_encode([
                'type' => 'broadcast_message',
                'title' => $event->title,
                'content' => $event->content,
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
