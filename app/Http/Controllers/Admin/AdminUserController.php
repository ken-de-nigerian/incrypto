<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustUserBalanceRequest;
use App\Http\Requests\UpdateWalletStatusRequest;
use App\Services\AdjustUserBalanceService;
use App\Services\GatewayHandlerService;
use App\Services\MarketDataService;
use App\Services\WalletService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Models\User;
use JsonException;
use Throwable;

class AdminUserController extends Controller
{
    protected GatewayHandlerService $gatewayHandler;
    protected MarketDataService $marketDataService;

    public function __construct(GatewayHandlerService $gatewayHandler, MarketDataService $marketDataService)
    {
        $this->gatewayHandler = $gatewayHandler;
        $this->marketDataService = $marketDataService;
    }

    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = 8;

        $usersQuery = User::query()
            ->where('role', '!=', 'admin')
            ->select('id', 'first_name', 'last_name', 'email', 'status')
            ->orderBy('id', 'desc');

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        if ($status) {
            $usersQuery->where('status', $status);
        }

        $users = $usersQuery
            ->with('profile')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ]
        ]);
    }

    public function show(User $user)
    {
        $cryptoSwapsCollection = $this->cryptoSwaps($user)->get();
        $receivedCryptosCollection = $this->receivedCryptos($user)->get();
        $sentCryptosCollection = $this->sentCryptos($user)->get();
        $referredUsersCollection = $this->referredUsers($user)->get();

        return Inertia::render('Admin/Users/Show', [
            'user' => $user->load(['profile', 'kyc', 'wallets']),
            'wallet_balances' => $this->walletBalances($user),

            // Use the formatted data structure
            'referred_users' => [
                'data' => $referredUsersCollection->toArray(),
                'total' => $referredUsersCollection->count(),
            ],
            'cryptoSwaps' => [
                'data' => $cryptoSwapsCollection->toArray(),
                'total' => $cryptoSwapsCollection->count(),
            ],
            'receivedCryptos' => [
                'data' => $receivedCryptosCollection->toArray(),
                'total' => $receivedCryptosCollection->count(),
            ],
            'sentCryptos' => [
                'data' => $sentCryptosCollection->toArray(),
                'total' => $sentCryptosCollection->count(),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function manageBalance(AdjustUserBalanceRequest $request, User $user, AdjustUserBalanceService $adjustUserBalanceService)
    {
        try {
            $adjustUserBalanceService->updateUserBalance(
                $user,
                $request->validated(),
            );
            return redirect()->back()->with('success', 'User wallet balance updated successfully.');
        } catch (Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * @throws JsonException
     */
    public function updateWalletStatus(User $user, UpdateWalletStatusRequest $request, WalletService $walletService)
    {
        $walletService->updateWalletStatus(
            $user,
            $request->validated(),
        );
        return back();
    }

    // Keep the helper methods as they are (returning a Query Builder)
    public function cryptoSwaps($user)
    {
        return $user->cryptoSwaps()
            ->select(['id', 'from_token', 'to_token', 'from_amount', 'to_amount', 'transaction_hash', 'chain', 'status', 'created_at'])
            ->latest();
    }

    public function receivedCryptos($user)
    {
        return $user->receivedCryptos()
            ->select(['id', 'token_symbol', 'wallet_address', 'amount', 'transaction_hash', 'status', 'created_at'])
            ->latest();
    }

    public function sentCryptos($user)
    {
        return $user->sentCryptos()
            ->select(['id', 'token_symbol', 'recipient_address', 'amount', 'transaction_hash', 'fee', 'status', 'created_at'])
            ->latest();
    }

    public function referredUsers($user)
    {
        return $user->referrals()
            ->select(['id', 'first_name', 'last_name', 'created_at'])
            ->latest();
    }

    private function walletBalances($user)
    {
        $fullWalletData = $this->getFullWalletData($user);
        $cryptoPrices = $this->getCryptoPrices();
        $processedWallets = [];
        $activeTotalUsdValue = 0;

        foreach ($fullWalletData as $symbol => $crypto) {
            $lowerSymbol = strtolower($crypto['symbol']);
            $currentPrice = $cryptoPrices[$lowerSymbol]['current_price'] ?? 0;
            $balance = $crypto['balance'] ?? 0;

            $status = (string)($crypto['status'] ?? '1');

            $usdValue = $balance * $currentPrice;
            $priceChange24h = $cryptoPrices[$lowerSymbol]['price_change_24h'] ?? 0;
            $priceChangePercentage = $cryptoPrices[$lowerSymbol]['price_change_percentage_24h'] ?? 0;
            $profitLoss = $balance * $priceChange24h;

            $processedWallets[] = [
                'name' => $crypto['name'] ?? $symbol,
                'image' => $crypto['image'] ?? asset('assets/images/crypto.png'),
                'balance' => $balance,
                'symbol' => $symbol,
                'usd_value' => $usdValue,
                'profit_loss' => $profitLoss,
                'price_change_percentage' => $priceChangePercentage,
                'is_profit' => $profitLoss >= 0,
                'status' => $status,
            ];

            if ($status !== '0') {
                $activeTotalUsdValue += $usdValue;
            }
        }

        usort($processedWallets, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return [
            'wallets' => $processedWallets,
            'totalUsdValue' => number_format($activeTotalUsdValue, 2)
        ];
    }

    private function getCryptoPrices(): array
    {
        $cryptocurrencies = $this->gatewayHandler->fetchGatewaysCrypto();
        $prices = [];

        foreach ($cryptocurrencies as $crypto) {
            $symbol = strtolower($crypto['coin']['symbol']);
            $prices[$symbol] = [
                'current_price' => $crypto['coin']['current_price'],
                'price_change_24h' => $crypto['coin']['price_change_24h'] ?? 0,
                'price_change_percentage_24h' => $crypto['coin']['price_change_percentage_24h'] ?? 0,
            ];
        }

        return $prices;
    }

    private function getFullWalletData($user)
    {
        $visibleWallets = [];
        try {
            $visibleWallets = json_decode($user->wallet_balance, true) ?? [];
        } catch (Exception $e) {
            Log::error('Error decoding user wallet_balance for user ' . $user->id . ': ' . $e->getMessage());
        }

        return $visibleWallets;
    }
}
