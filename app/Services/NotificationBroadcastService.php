<?php

namespace App\Services;

use App\Events\DatabaseNotificationDispatchedEvent;
use App\Events\EmailNotificationDispatchedEvent;
use App\Events\DatabaseAndEmailNotificationDispatchedEvent;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;

class NotificationBroadcastService
{
    private const CHUNK_SIZE = 500;

    /**
     * Broadcast notifications based on the specified type
     *
     * @param array $data
     * @return bool
     * @throws Exception
     */
    public function broadcast(array $data): bool
    {
        try {
            $notificationType = $data['deliveryMethod'] ?? null;

            match ($notificationType) {
                'both' => $this->broadcastDatabaseAndEmail($data),
                'db' => $this->broadcastDatabase($data),
                'email' => $this->broadcastEmail($data),
                default => throw new Exception("Invalid notification type: $notificationType"),
            };

            return true;
        } catch (Exception $e) {
            Log::error('Failed to broadcast notification: ' . $e->getMessage(), [
                'data' => $data,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Broadcast both database and email notifications
     *
     * @param array $data
     * @return void
     */
    private function broadcastDatabaseAndEmail(array $data): void
    {
        User::whereNotNull('email')
            ->where('role', '!=', 'admin')
            ->chunk(self::CHUNK_SIZE, function ($users) use ($data) {
                foreach ($users as $user) {
                    event(new DatabaseAndEmailNotificationDispatchedEvent(
                        user: $user,
                        title: $data['title'],
                        content: $data['content']
                    ));
                }
            });
    }

    /**
     * Broadcast database-only notifications
     *
     * @param array $data
     * @return void
     */
    private function broadcastDatabase(array $data): void
    {
        User::whereNotNull('email')
            ->where('role', '!=', 'admin')
            ->chunk(self::CHUNK_SIZE, function ($users) use ($data) {
                foreach ($users as $user) {
                    event(new DatabaseNotificationDispatchedEvent(
                        user: $user,
                        title: $data['title'],
                        content: $data['content']
                    ));
                }
            });
    }

    /**
     * Broadcast email-only notifications
     *
     * @param array $data
     * @return void
     */
    private function broadcastEmail(array $data): void
    {
        User::whereNotNull('email')
            ->where('role', '!=', 'admin')
            ->chunk(self::CHUNK_SIZE, function ($users) use ($data) {
                foreach ($users as $user) {
                    event(new EmailNotificationDispatchedEvent(
                        user: $user,
                        title: $data['title'],
                        content: $data['content']
                    ));
                }
            });
    }
}
