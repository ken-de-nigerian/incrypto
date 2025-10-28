<?php

namespace App\Services;

use App\Events\TradingAccountDebited;
use App\Events\TradingAccountFunded;
use App\Http\Resources\TokenResource;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Random\RandomException;
use Throwable;

class TradeCryptoPageService
{
    public function __construct(
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    ) {
    }

    public function getData(User $user): array
    {
        $walletService = new WalletService($user, $this->gatewayHandler);
        $marketData = $this->marketDataService->getMarketDataBySymbol();
        $userBalances = $walletService->getBalances();
        $fullWalletData = $walletService->getFullWalletData();
        $gatewaysByCode = collect($this->gatewayHandler->getGateways())->keyBy('method_code');

        // Use a Resource Collection to format the token array cleanly
        $tokens = TokenResource::collection(
            collect($userBalances)->map(function ($balance, $symbol) use ($marketData, $fullWalletData, $gatewaysByCode) {
                return [
                    'symbol' => $symbol,
                    'balance' => $balance,
                    'market_data' => $marketData[$this->marketDataService->getBaseSymbol($symbol)] ?? [],
                    'wallet_data' => $fullWalletData[$symbol] ?? [],
                    'gateway' => isset($fullWalletData[$symbol]['id']) ? $gatewaysByCode->get($fullWalletData[$symbol]['id']) : null,
                ];
            })->values()
        );

        return [
            'tokens' => $tokens->resolve(),
            'userBalances' => (object) $userBalances,
            'prices' => (object) $this->marketDataService->getPrices(),
        ];
    }

    /**
     * @throws RandomException
     * @throws Throwable
     */
    public function executeAccountFund(User $user, array $validatedData)
    {
        $fromToken = strtoupper($validatedData['source_symbol']);
        $fromAmount = (float) $validatedData['source_amount'];
        $toAmount = (float) $validatedData['estimated_funds'];
        $baseFromToken = $this->marketDataService->getBaseSymbol($fromToken);

        if (!$this->marketDataService->isValidToken($baseFromToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        if (!$walletService->hasSufficientBalance($fromToken, $fromAmount)) {
            $this->logFailedSwap($user, $validatedData);
            throw new Exception('Insufficient balance to perform this swap.');
        }

        $userProfile = DB::transaction(function () use ($user, $walletService, $fromToken, $fromAmount, $toAmount) {
            $walletService->debit($fromToken, $fromAmount);
            $walletService->save();

            $user->profile->increment('live_trading_balance', $toAmount);

            return $user->profile;
        });

        // Dispatch the event with the new transaction data
        event(new TradingAccountFunded($userProfile, $fromToken, $fromAmount, $toAmount));

        return $userProfile;
    }

    /**
     * @throws Throwable
     */
    public function executeAccountFundWithdrawal(User $user, array $validatedData)
    {
        $toToken = strtoupper($validatedData['target_symbol']);
        $fromAmount = (float) $validatedData['usd_amount'];
        $toAmount = (float) $validatedData['estimated_crypto'];
        $baseFromToken = $this->marketDataService->getBaseSymbol($toToken);

        if (!$this->marketDataService->isValidToken($baseFromToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        $userProfile = DB::transaction(function () use ($user, $walletService, $toToken, $fromAmount, $toAmount) {
            $walletService->credit($toToken, $toAmount);
            $walletService->save();

            $user->profile->decrement('live_trading_balance', $fromAmount);

            return $user->profile;
        });

        // Dispatch the event with the new transaction data
        event(new TradingAccountDebited($userProfile, $toToken, $fromAmount, $toAmount));

        return $userProfile;
    }
}
