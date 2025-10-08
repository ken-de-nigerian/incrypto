<?php

namespace App\Services;

use App\Models\CryptoSwap;
use App\Models\User;
use App\Notifications\SwapSuccessfulNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CryptoSwapService
{
    protected MarketDataService $marketDataService;
    protected GatewayHandlerService $gatewayHandler;

    public function __construct(MarketDataService $marketDataService, GatewayHandlerService $gatewayHandler)
    {
        $this->marketDataService = $marketDataService;
        $this->gatewayHandler = $gatewayHandler;
    }

    /**
     * Gathers all data needed for the Inertia swap page.
     */
    public function getSwapPageData(User $user): array
    {
        $walletService = new WalletService($user, $this->gatewayHandler);
        $marketData = $this->marketDataService->getMarketDataBySymbol();
        $userBalances = $walletService->getBalances();

        $tokens = [];
        $fullWalletData = $walletService->getFullWalletData();
        foreach ($userBalances as $symbol => $balance) {
            $baseSymbol = $this->marketDataService->getBaseSymbol($symbol);
            $coin = $marketData[$baseSymbol] ?? [];
            $tokens[] = [
                'symbol' => $symbol,
                'name' => $coin['name'] ?? 'Unknown Token',
                'logo' => $coin['image'] ?? asset('assets/images/default-crypto.png'),
                'address' => '0x' . substr(md5($symbol), 0, 40),
                'decimals' => $coin['decimals'] ?? 18,
                'chain' => $fullWalletData[$symbol]['chain'] ?? 'Ethereum',
                'price_change_24h' => $coin['price_change_percentage_24h'] ?? 0,
            ];
        }

        return [
            'tokens' => $tokens,
            'userBalances' => (object) $userBalances,
            'prices' => (object) $this->marketDataService->getPrices(),
            'transactionHistory' => $this->getTransactionHistory($user),
            'portfolioChange24h' => $this->calculatePortfolioChange($userBalances, $marketData),
            'gasPrices' => $this->marketDataService->getGasPrices(),
            'popularTokens' => $this->marketDataService->getPopularTokens(6),
        ];
    }

    /**
     * Executes the crypto swap.
     * @throws Exception|Throwable
     */
    public function executeSwap(User $user, array $validatedData): CryptoSwap
    {
        $fromToken = strtoupper($validatedData['fromToken']);
        $toToken = strtoupper($validatedData['toToken']);
        $fromAmount = (float) $validatedData['fromAmount'];
        $toAmount = (float) $validatedData['toAmount'];
        $baseFromToken = $this->marketDataService->getBaseSymbol($fromToken);
        $baseToToken = $this->marketDataService->getBaseSymbol($toToken);

        if (!$this->marketDataService->isValidToken($baseFromToken) || !$this->marketDataService->isValidToken($baseToToken)) {
            throw new Exception('Invalid token provided.');
        }

        $walletService = new WalletService($user, $this->gatewayHandler);

        if (!$walletService->hasSufficientBalance($fromToken, $fromAmount)) {
            $this->logFailedSwap($user, $validatedData);
            throw new Exception('Insufficient balance to perform this swap.');
        }

        $swapRecord = DB::transaction(function () use ($user, $walletService, $fromToken, $toToken, $fromAmount, $toAmount, $validatedData) {
            $walletService->debit($fromToken, $fromAmount);
            $walletService->credit($toToken, $toAmount);
            $walletService->save();

            return CryptoSwap::create([
                'user_id' => $user->id,
                'from_token' => $fromToken,
                'to_token' => $toToken,
                'from_amount' => $fromAmount,
                'to_amount' => $toAmount,
                'transaction_hash' => '0x' . bin2hex(random_bytes(32)),
                'chain' => $validatedData['chain'],
                'status' => 'success',
            ]);
        });

        $this->sendNotifications($user, $swapRecord);

        return $swapRecord;
    }

    /**
     * @throws Exception
     */
    public function approveToken(array $validatedData): void
    {
        $baseToken = $this->marketDataService->getBaseSymbol($validatedData['token']);
        if (!$this->marketDataService->isValidToken($baseToken)) {
            throw new Exception('Token not found for approval.');
        }
    }

    private function getTransactionHistory(User $user): array
    {
        return CryptoSwap::where('user_id', $user->id)
            ->latest()
            ->take(2)
            ->get()
            ->map(fn($tx) => [
                'id' => $tx->id,
                'date' => $tx->created_at->toDateTimeString(),
                'from' => $tx->from_token,
                'to' => $tx->to_token,
                'amount' => number_format($tx->from_amount, 8, '.', ''),
                'status' => $tx->status,
                'hash' => $tx->transaction_hash,
            ])->toArray();
    }

    private function calculatePortfolioChange(array $userBalances, array $marketDataBySymbol): float
    {
        $currentValue = 0;
        $previousValue = 0;

        foreach ($userBalances as $symbol => $balance) {
            $baseSymbol = $this->marketDataService->getBaseSymbol($symbol);
            $coin = $marketDataBySymbol[$baseSymbol] ?? [];
            $currentPrice = $coin['current_price'] ?? 0;
            $priceChange = $coin['price_change_percentage_24h'] ?? 0;

            if ($currentPrice > 0) {
                $currentValue += $balance * $currentPrice;
                $previousPrice = $currentPrice / (1 + ($priceChange / 100));
                $previousValue += $balance * $previousPrice;
            }
        }

        return $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0;
    }

    private function sendNotifications(User $user, CryptoSwap $swapRecord): void
    {
        $user->notify(new SwapSuccessfulNotification($swapRecord));
        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new SwapSuccessfulNotification($swapRecord));
        }
    }

    private function logFailedSwap(User $user, array $data): void
    {
        CryptoSwap::create([
            'user_id' => $user->id,
            'from_token' => $data['fromToken'],
            'to_token' => $data['toToken'],
            'from_amount' => $data['fromAmount'],
            'to_amount' => $data['toAmount'],
            'transaction_hash' => 'N/A',
            'chain' => $data['chain'],
            'status' => 'failed',
            'notes' => 'Insufficient balance',
        ]);
    }
}
