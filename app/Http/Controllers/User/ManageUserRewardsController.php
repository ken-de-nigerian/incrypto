<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ManageUserRewardsController extends Controller
{
    /**
     * Display the user's referrals and rewards page.
     *
     * @return Response
     */
    public function index()
    {
        $user = Auth::user();
        return Inertia::render('User/Rewards', [
            'referralData' => [
                'referral_code' => $user->profile->referral_code,
                'referral_link' => $user?->referralLink(),
            ],
            'referrals' => $this->referredUsers($user),
            'statistics' => $this->getStatisticsData($user),
        ]);
    }

    /**
     * Fetches the user's referred users list with necessary data.
     * In a real application, this would join with trades/earnings tables and paginate.
     *
     * @param User $user
     * @return array
     */
    protected function referredUsers(User $user)
    {
        $referrals = $user->referrals()
            ->with(['profile' => function ($query) {
                $query->select('user_id', 'profile_photo_path');
            }])
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'status',
                'created_at',
            ])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return $referrals->map(fn ($referral) => [
            'id' => $referral->id,
            'first_name' => $referral->first_name,
            'last_name' => $referral->last_name,
            'email' => $referral->email,
            'status' => $referral->status,
            'created_at' => $referral->created_at,
            'avatar' => $referral->profile->profile_photo_path ?? null,
        ])->toArray();
    }


    /**
     * Gets the statistics data.
     *
     * @param User $user
     * @return array
     */
    protected function getStatisticsData(User $user): array
    {
        $totalReferrals = $user->referrals()->count();
        $activeReferrals = $user->referrals()->where('status', 'active')->count();
        $thisMonthReferrals = $user->referrals()->whereMonth('created_at', now()->month)->count();
        $conversionRate = $totalReferrals > 0 ? (float)number_format(($activeReferrals / $totalReferrals) * 100, 2) : 0;

        return [
            'total_referrals' => $totalReferrals,
            'active_referrals' => $activeReferrals,
            'conversion_rate' => $conversionRate,
            'this_month_referrals' => $thisMonthReferrals,
        ];
    }
}
