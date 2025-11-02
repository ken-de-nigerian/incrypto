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
use Illuminate\Support\Facades\Auth;
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
        $pageData['forexPairs'] = $this->getForexPairs();
        $pageData['trades'] = $this->getUserTrades($user);

        return Inertia::render('User/Trade/Forex', $pageData);
    }

    /**
     * Get forex pairs data from database or API
     * Adjust based on your actual data source
     */
    private function getForexPairs(): array
    {
        return (new GatewayHandlerService())->fetchForexPairs();
    }

    /**
     * Get user's trading history
     */
    private function getUserTrades($user): array
    {
        return $user->trades()->latest()->get()->toArray();
    }

    /**
     * @throws Throwable
     */
    public function executeForex(executeForexRequest $request, ForexTradeService $forexTradeService)
    {
        try {
            $forexTradeService->executeForex(
                $request->user(),
                $request->validated()
            );

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
            $forexTradeService->closeForex(
                $request->user(),
                $request->validated(),
                $trade
            );

            return $this->notify('success', 'Trade closed successfully')->toBack();
        }catch (Exception $e) {
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
            $this->tradeCrypto->executeAccountFund(
                $request->user(),
                $request->validated()
            );

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
            $this->tradeCrypto->executeAccountFundWithdrawal(
                $request->user(),
                $validated
            );

            return $this->notify('success', "Converted \$" . $validated['usd_amount'] . " USD to " . $validated['estimated_crypto'] . " " . $validated['target_symbol'])->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
