<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoSwap;
use App\Models\InvestmentHistory;
use App\Models\KycSubmission;
use App\Models\ReceivedCrypto;
use App\Models\SendCrypto;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request) {
        return Inertia::render('Admin/Dashboard', [
            'adminStatsData' => $this->adminStats(),
            'pendingActions' => $this->pendingActions(),
            'recentUsers' => $this->recentUsers(),
        ]);
    }

    protected function adminStats()
    {
        return [
            'total_users' => User::query()
                ->where('role', '!=', 'admin')
                ->count(),
            'total_active_users' => User::query()
                ->where('role', '!=', 'admin')
                ->where('status', 'active')
                ->count(),
            'total_suspended_users' => User::query()
                ->where('role', '!=', 'admin')
                ->where('status', 'suspended')
                ->count(),
            'total_sent' => SendCrypto::query()
                ->count(),
            'total_received' => ReceivedCrypto::query()
                ->count(),
            'total_swaps' => CryptoSwap::query()
                ->count(),
            'total_trades' => Trade::query()
                ->count(),
            'total_investments' => InvestmentHistory::query()
                ->count()
        ];
    }

    protected function pendingActions()
    {
        $kycActions = KycSubmission::where('status', 'pending')
            ->latest()
            ->limit(3)
            ->whereHas('user')
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn ($action) => [
                'id' => 'kyc-' . $action->id,
                'type' => 'KYC Review',
                'user' => $action->user->first_name . ' ' . $action->user->last_name,
                'link' => route('admin.kyc.index'),
                'created_at' => $action->created_at->timestamp,
            ])->values();

        $sentActions = SendCrypto::where('status', 'pending')
            ->latest()
            ->limit(3)
            ->whereHas('user')
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn ($action) => [
                'id' => 'sent-' . $action->id,
                'type' => 'Sent Crypto Approval',
                'user' => $action->user->first_name . ' ' . $action->user->last_name,
                'link' => route('admin.transaction.index', ['tab' => 'sent']),
                'created_at' => $action->created_at->timestamp,
            ])->values();

        $receivedActions = ReceivedCrypto::where('status', 'pending')
            ->latest()
            ->limit(3)
            ->whereHas('user')
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(fn ($action) => [
                'id' => 'received-' . $action->id,
                'type' => 'Received Crypto Approval',
                'user' => $action->user->first_name . ' ' . $action->user->last_name,
                'link' => route('admin.transaction.index', ['tab' => 'received']),
                'created_at' => $action->created_at->timestamp,
            ])->values();

        return collect($kycActions->all())
            ->merge($sentActions->all())
            ->merge($receivedActions->all())
            ->sortByDesc('created_at')
            ->take(3)
            ->values();
    }

    protected function recentUsers()
    {
        return User::query()
            ->where('role', '!=', 'admin')
            ->latest()
            ->limit(5)
            ->with('profile')
            ->get(['id', 'first_name', 'last_name', 'email', 'created_at', 'status'])
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'registered_at' => $user->created_at->diffForHumans(),
                    'status' => $user->status,
                    'profile' => $user->profile,
                ];
            })
            ->values();
    }
}
