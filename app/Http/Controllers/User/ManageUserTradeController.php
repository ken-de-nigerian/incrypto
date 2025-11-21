<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseTradeRequest;
use App\Http\Requests\ExecuteInvestmentRequest;
use App\Http\Requests\ExecuteTradeRequest;
use App\Http\Requests\FundAccountRequest;
use App\Http\Requests\StartCopyRequest;
use App\Http\Requests\WithdrawAccountRequest;
use App\Models\CopyTrade;
use App\Models\MasterTrader;
use App\Models\Plan;
use App\Models\Trade;
use App\Services\TradeService;
use App\Services\GatewayHandlerService;
use App\Services\TradeCryptoPageService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ManageUserTradeController extends Controller
{
    public TradeCryptoPageService $tradeCrypto;
    public WalletService $walletService;

    public function __construct(TradeCryptoPageService $tradeCryptoPageService, WalletService $walletService)
    {
        $this->tradeCrypto = $tradeCryptoPageService;
        $this->walletService = $walletService;
    }

    /**
     * Display the receive crypto page.
     */
    public function index(): Response
    {
        $pageData = $this->tradeCrypto->getData(Auth::user());
        return Inertia::render('User/Trade/Index', $pageData);
    }

    /**
     * Display the Forex trading page with all required data
     */
    public function forex(): Response
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);
        $pageData['forexPairs'] = (new GatewayHandlerService())->getAllForexPairs();
        $pageData['trades'] = $user->trades()
            ->where('category', 'forex')
            ->latest()
            ->get()
            ->toArray();

        return Inertia::render('User/Trade/Forex', $pageData);
    }

    /**
     * Display the Stock trading page with all required data
     */
    public function stock(): Response
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);
        $pageData['stockPairs'] = (new GatewayHandlerService())->getAllStocksPairs();
        $pageData['stocks'] = $user->trades()
            ->where('category', 'stock')
            ->latest()
            ->get()
            ->toArray();

        return Inertia::render('User/Trade/Stock', $pageData);
    }

    /**
     * Display the Crypto trading page with all required data
     */
    public function crypto(): Response
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);
        $pageData['cryptoPairs'] = (new GatewayHandlerService())->getAllCryptoPairs();
        $pageData['cryptos'] = $user->trades()
            ->where('category', 'crypto')
            ->latest()
            ->get()
            ->toArray();

        return Inertia::render('User/Trade/Crypto', $pageData);
    }

    /**
     * Display the investment plans page
     */
    public function investment(): Response
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        $pageData['plans'] = Plan::where('status', 'active')
            ->with('plan_time_settings')
            ->paginate(9)
            ->toArray();

        return Inertia::render('User/Trade/Investment', $pageData);
    }

    /**
     * Display the investment history page
     */
    public function investmentHistory(Request $request)
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        // Build investment history query
        $query = $user->investmentHistories()
            ->with('plan.plan_time_settings');

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('id', 'like', "%$searchTerm%")
                    ->orWhereHas('plan', function ($planQuery) use ($searchTerm) {
                        $planQuery->where('name', 'like', "%$searchTerm%");
                    });
            });
        }

        // Apply date filter
        if ($request->filled('date') && $request->date !== 'all') {
            $now = now();
            switch ($request->date) {
                case 'today':
                    $query->whereDate('created_at', $now->toDateString());
                    break;
                case 'week':
                    $query->where('created_at', '>=', $now->subDays(7));
                    break;
                case 'month':
                    $query->where('created_at', '>=', $now->subDays(30));
                    break;
            }
        }

        // Apply sorting
        $sortBy = $request->input('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->oldest();
                break;
            case 'amount_high':
                $query->orderBy('amount', 'desc');
                break;
            case 'amount_low':
                $query->orderBy('amount');
                break;
            case 'profit_high':
                $query->orderByRaw('(interest * repeat_time_count) DESC');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }

        $pageData['investment_histories'] = $query->paginate(20)
            ->withQueryString();

        $allInvestments = $user->investmentHistories()->get();
        $totalEarned = $allInvestments->reduce(function ($carry, $item) {
            return $carry + ($item->interest * $item->repeat_time_count);
        }, 0);

        $pageData['stats'] = [
            'total_invested' => $allInvestments->sum('amount'),
            'total_earned' => $totalEarned,
            'active_investments' => $allInvestments->where('status', 'running')->count(),
            'completed_investments' => $allInvestments->where('status', 'completed')->count(),
            'total_profit' => $totalEarned,
        ];

        return Inertia::render('User/Trade/Partials/InvestmentHistory', $pageData);
    }

    /**
     * Display the copy trading network page.
     */
    public function network(Request $request)
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        // Get user's active copied trader IDs
        $activeCopiedTraderIds = $user->copyTrades()
            ->where('status', 'active')
            ->pluck('master_trader_id')
            ->toArray();

        // Build master traders query
        $query = MasterTrader::where('is_active', true)
            ->where('user_id', '!=', $user->id)
            ->with([
                'user.profile',
                'copyTrades' => function ($query) {
                    $query->where('status', 'active');
                },
                'activeCopyTrades'
            ])
            ->withCount('activeCopyTrades as copiers_count');

        // Apply expertise filter
        if ($request->filled('expertise') && $request->expertise !== 'all') {
            $query->where('expertise', $request->expertise);
        }

        // Apply free trial filter
        if ($request->filled('free_trial') && $request->boolean('free_trial')) {
            $query->where(function ($q) {
                $q->whereNull('commission_rate')
                    ->orWhere('commission_rate', 0);
            });
        }

        // Apply search filter
        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->whereHas('user', function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%$searchTerm%")
                    ->orWhere('last_name', 'like', "%$searchTerm%")
                    ->orWhere('email', 'like', "%$searchTerm%");
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
            case 'risk':
            default:
                $query->orderBy('risk_score', 'asc');
                break;
        }

        $pageData['masterTraders'] = $query->paginate(8)
            ->withQueryString()
            ->through(function ($trader) use ($activeCopiedTraderIds) {
                $trader->is_copied = in_array($trader->id, $activeCopiedTraderIds);
                return $trader;
            });

        // Pass active copied trader IDs to frontend
        $pageData['activeCopiedTraderIds'] = $activeCopiedTraderIds;

        return Inertia::render('User/Trade/Network', $pageData);
    }

    /**
     * Display the page for all copied traders.
     */
    public function networkCopied()
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        // Get user's copy trades with all related data
        $pageData['copyTrades'] = $user->copyTrades()
            ->with([
                'masterTrader.user.profile',
                'transactions' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }
            ])
            ->latest()
            ->paginate(20);

        // Calculate stats for copy trades
        $allCopyTrades = $user->copyTrades()->get();

        $pageData['stats'] = [
            'total_active' => $allCopyTrades->where('status', 'active')->count(),
            'total_profit' => $allCopyTrades->sum('current_profit'),
            'total_loss' => $allCopyTrades->sum('current_loss'),
            'total_commission' => $allCopyTrades->sum('total_commission_paid'),
            'net_profit' => $allCopyTrades->sum('current_profit') - $allCopyTrades->sum('current_loss'),
            'active_traders' => $allCopyTrades->where('status', 'active')
                ->pluck('master_trader_id')
                ->unique()
                ->count(),
        ];

        return Inertia::render('User/Trade/Partials/MyCopyTrades', $pageData);
    }

    /**
     * @throws Throwable
     */
    public function startCopy(StartCopyRequest $request, MasterTrader $masterTrader, TradeService $tradeService)
    {
        try {
            $result = $tradeService->startCopy(
                $masterTrader,
                $request->user(),
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to start copying trader - invalid state')->toBack();
            }

            return $this->notify('success', 'Successfully started copying ' . $masterTrader->user->first_name)
                ->toRoute('user.trade.network.copied');
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Pause a copy trade.
     */
    public function pauseCopy(CopyTrade $copyTrade)
    {
        $user = Auth::user();

        // Ensure the copy trade belongs to the user
        if ($copyTrade->user_id !== $user->id) {
            abort(403);
        }

        try {
            $copyTrade->pause();
            return $this->notify('success', 'Copy trade paused successfully.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Resume a copy trade.
     */
    public function resumeCopy(CopyTrade $copyTrade)
    {
        $user = Auth::user();

        // Ensure the copy trade belongs to the user
        if ($copyTrade->user_id !== $user->id) {
            abort(403);
        }

        try {
            $copyTrade->resume();
            return $this->notify('success', 'Copy trade resumed successfully.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Stop a copy trade.
     */
    public function stopCopy(CopyTrade $copyTrade)
    {
        $user = Auth::user();

        // Ensure the copy trade belongs to the user
        if ($copyTrade->user_id !== $user->id) {
            abort(403);
        }

        try {
            $copyTrade->stop();
            return $this->notify('success', 'Copy trade stopped successfully.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function executeInvestment(ExecuteInvestmentRequest $request, TradeService $tradeService)
    {
        try {
            $result = $tradeService->executeInvestment(
                $request->user(),
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to execute investment - invalid state')->toBack();
            }

            return $this->notify('success', 'Investment executed successfully')
                ->toRoute('user.trade.investment.history');
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Fetch chart data for a specific pair
     */
    public function getChartData(Request $request, string $symbol)
    {
        try {

            // Decode the symbol
            $decodedSymbol = urldecode($symbol);
            $category = $request->category;
            $gatewayService = new GatewayHandlerService();
            $chartData = $gatewayService->fetchTradeChartData($decodedSymbol, $category);

            return response()->json($chartData);
        } catch (Exception $e) {
            Log::error('Failed to fetch chart data', [
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch chart data'
            ], 500);
        }
    }

    /**
     * Get paginated chart data for infinite scroll
     */
    public function getPaginatedChartData(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'category' => 'required|string|in:forex,stock,crypto',
            'cursor' => 'required|string',
            'from' => 'nullable|string',
            'to' => 'nullable|string'
        ]);

        $decodedSymbol = urldecode($validated['symbol']);
        $gatewayService = new GatewayHandlerService();
        $data = $gatewayService->fetchPaginatedChartData(
            $decodedSymbol,
            $validated['category'],
            $validated['cursor'],
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        return response()->json($data);
    }

    /**
     * @throws Throwable
     */
    public function executeTrade(ExecuteTradeRequest $request, TradeService $tradeService)
    {
        try {
            $result = $tradeService->executeTrade(
                $request->user(),
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to execute trade - invalid state')->toBack();
            }

            return $this->notify('success', 'Trade executed successfully')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function closeTrade(CloseTradeRequest $request, Trade $trade, TradeService $tradeService)
    {
        try {
            $result = $tradeService->closeTrade(
                $request->user(),
                $request->validated(),
                $trade
            );

            if (!$result) {
                return $this->notify('error', 'Failed to close trade - invalid state')->toBack();
            }

            return $this->notify('success', 'Trade closed successfully')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Handle funding the live account (Crypto to USD conversion).
     * @throws Throwable
     */
    public function fundAccount(FundAccountRequest $request)
    {
        try {
            $result = $this->tradeCrypto->executeAccountFund(
                $request->user(),
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to fund account - invalid state')->toBack();
            }

            return $this->notify('success', 'Your account has been successfully funded.')->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Handle withdrawal (USD to Crypto conversion).
     * @throws Throwable
     */
    public function withdrawAccount(WithdrawAccountRequest $request)
    {
        $validated = $request->validated();

        try {
            $result = $this->tradeCrypto->executeAccountFundWithdrawal(
                $request->user(),
                $validated
            );

            if (!$result) {
                return $this->notify('error', 'Failed to withdraw funds - invalid state')->toBack();
            }

            return $this->notify('success', "Converted \$" . $validated['usd_amount'] . " USD to " . $validated['estimated_crypto'] . " " . $validated['target_symbol'])->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
