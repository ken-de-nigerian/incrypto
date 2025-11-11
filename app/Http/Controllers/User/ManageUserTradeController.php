<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\closeForexRequest;
use App\Http\Requests\executeForexRequest;
use App\Http\Requests\FundAccountRequest;
use App\Http\Requests\WithdrawAccountRequest;
use App\Models\Trade;
use App\Services\ForexTradeService;
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
        $pageData['forexPairs'] = (new GatewayHandlerService())->getAllPairs();
        $pageData['trades'] = $user->trades()->latest()->get()->toArray();

        return Inertia::render('User/Trade/Forex', $pageData);
    }

    /**
     * Fetch forex chart data for a specific pair
     */
    public function getForexChartData(Request $request, string $symbol)
    {
        try {

            // Decode the symbol
            $decodedSymbol = urldecode($symbol);
            $gatewayService = new GatewayHandlerService();
            $chartData = $gatewayService->fetchForexChartData($decodedSymbol);

            return response()->json($chartData);
        } catch (Exception $e) {
            Log::error('Failed to fetch forex chart data', [
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
     * @throws Throwable
     */
    public function executeForex(executeForexRequest $request, ForexTradeService $forexTradeService)
    {
        try {
            $result = $forexTradeService->executeForex(
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
    public function closeForex(closeForexRequest $request, Trade $trade, ForexTradeService $forexTradeService)
    {
        try {
            $result = $forexTradeService->closeForex(
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
