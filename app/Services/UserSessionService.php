<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class UserSessionService
{
    public function getActiveSessions(Request $request): array
    {
        if (!$request->user()) return [];

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderBy('last_activity', 'desc')
            ->get();

        $agent = new Agent();
        $activeSessions = [];

        foreach ($sessions as $session) {
            $agent->setUserAgent($session->user_agent);
            $activeSessions[] = [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'device' => $agent->device() ?: 'Unknown',
                'platform' => $agent->platform() ?: 'Unknown',
                'browser' => $agent->browser() ?: 'Unknown',
                'last_activity' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                'is_current' => $session->id === $request->session()->getId(),
            ];
        }
        return $activeSessions;
    }
}
