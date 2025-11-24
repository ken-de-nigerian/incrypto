<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterTraderRequest;
use App\Http\Requests\UpdateMasterTraderRequest;
use App\Models\MasterTrader;
use App\Models\User;
use DB;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class AdminCopyTradersController extends Controller
{
    public function index(Request $request)
    {
        $query = MasterTrader::with([
            'user.profile',
            'activeCopyTrades'
        ])->withCount('activeCopyTrades as copiers_count');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        // Apply expertise filter
        if ($request->filled('expertise') && $request->expertise !== 'all') {
            $query->where('expertise', $request->expertise);
        }

        // Apply free trial filter
        if ($request->boolean('free_trial')) {
            $query->where(function ($q) {
                $q->whereNull('commission_rate')
                    ->orWhere('commission_rate', 0);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where(function($subQ) use ($searchTerm) {
                    $subQ->where('email', 'like', "%$searchTerm%")
                        ->orWhere('first_name', 'like', "%$searchTerm%")
                        ->orWhere('last_name', 'like', "%$searchTerm%");
                });
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'risk');
        switch ($sortBy) {
            case 'gain':
                $query->orderBy('gain_percentage', 'desc');
                break;
            case 'copiers':
                $query->orderBy('copiers_count', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at');
                break;
            default:
                $query->orderBy('risk_score');
                break;
        }

        $pageData['masterTraders'] = $query->paginate(12)->withQueryString();

        // Get statistics
        $pageData['statistics'] = [
            'total_traders' => MasterTrader::count(),
            'active_traders' => MasterTrader::where('is_active', true)->count(),
            'inactive_traders' => MasterTrader::where('is_active', false)->count(),
            'total_copiers' => DB::table('copy_trades')
                ->where('status', 'active')
                ->distinct('user_id')
                ->count('user_id'),
            'total_copy_trades' => DB::table('copy_trades')
                ->where('status', 'active')
                ->count(),
        ];

        $pageData['availableUsers'] = User::whereDoesntHave('masterTrader')
        ->where('role', '!=', 'admin')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->select(['id', 'first_name', 'last_name', 'email'])
            ->get();

        return Inertia::render('Admin/CopyTraders/Index', $pageData);
    }

    /**
     * @throws Throwable
     */
    public function store(StoreMasterTraderRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                MasterTrader::create($validated);
            });

            return $this->notify('success', 'Master trader created successfully.')
                ->toBack();

        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateMasterTraderRequest $request, MasterTrader $masterTrader)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $masterTrader) {
                $masterTrader->update($validated);
            });

            return $this->notify('success', 'Master trader updated successfully.')
                ->toBack();

        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    public function toggleStatus(MasterTrader $masterTrader)
    {
        try {
            $masterTrader->update([
                'is_active' => !$masterTrader->is_active
            ]);

            $status = $masterTrader->is_active ? 'activated' : 'deactivated';

            return $this->notify('success', "Trader status $status successfully.")
                ->toBack();

        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(MasterTrader $masterTrader)
    {
        try {
            DB::transaction(function () use ($masterTrader) {
                $masterTrader->delete();
            });

            $copierCount = $masterTrader->activeCopyTrades()->count();

            return $this->notify('success', "Master trader deleted successfully. $copierCount active copy trade(s) were stopped.")
                ->toBack();

        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
