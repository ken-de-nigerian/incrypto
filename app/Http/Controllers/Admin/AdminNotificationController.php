<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\NotificationBroadcastRequest;
use App\Services\NotificationBroadcastService;
use Exception;

class AdminNotificationController extends Controller
{
    public function broadcast(NotificationBroadcastRequest $request, NotificationBroadcastService  $notificationBroadcastService)
    {
        try {
            $notificationBroadcastService->broadcast(
                $request->validated(),
            );
            return $this->notify('success', __('Broadcast notification sent successfully'))->toBack();
        } catch (Exception $e) {
            return $this->notify('error', $e->getMessage())->toBack();
        }
    }
}
