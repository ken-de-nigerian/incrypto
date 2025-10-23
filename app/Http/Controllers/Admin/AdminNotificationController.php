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
            return redirect()->back()->with('success', __('Broadcast notification sent successfully'));
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['subject' => $e->getMessage()]);
        }
    }
}
