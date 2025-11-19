<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseTradeRequest;
use App\Http\Requests\ExecuteInvestmentRequest;
use App\Http\Requests\ExecuteTradeRequest;
use App\Http\Requests\FundAccountRequest;
use App\Http\Requests\WithdrawAccountRequest;
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

    public function investment(): Response
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        $pageData['plans'] = Plan::where('status', 'active')
            ->with('plan_time_settings')
            ->paginate(9)
            ->toArray();

        $pageData['investment_histories'] = $user->investmentHistories()
            ->with('plan.plan_time_settings')
            ->latest()
            ->paginate(20)
            ->toArray();

        return Inertia::render('User/Trade/Investment', $pageData);
    }

    /**
     * Display the copy trading network page.
     */
    public function network()
    {
        $user = Auth::user();
        $pageData = $this->tradeCrypto->getData($user);

        // Get active master traders with relationships
        $pageData['masterTraders'] = MasterTrader::where('is_active', true)
            ->where('user_id', '!=', $user->id)
            ->with([
                'user.profile',
                'copyTrades' => function ($query) {
                    $query->where('status', 'active');
                },
                'activeCopyTrades'
            ])
            ->withCount('activeCopyTrades as copiers_count')
            ->orderBy('gain_percentage', 'desc')
            ->paginate(8);

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

        return Inertia::render('User/Trade/Network', $pageData);
    }

    /**
     * Display the page for all copied traders.
     */
    public function copied()
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

            return $this->notify('success', 'Investment executed successfully')->toBack();
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
