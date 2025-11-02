<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalletConnectionRequest;
use App\Services\GatewayHandlerService;
use App\Services\WalletConnectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class ManageUserWalletConnectController extends Controller
{
    protected WalletConnectionService $walletService;

    public function __construct(WalletConnectionService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index()
    {
        // Get All Wallets Listed
        $wallets = (new GatewayHandlerService())->getWallets();

        return Inertia::render('User/Wallet/Index', [
            'userWallet' => auth()->user()->load(['wallets']),
            'wallets' => $wallets,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Get the wallet details from market data
        $wallet = (new GatewayHandlerService())->getWallet($request->id);
        if (!$wallet){
            return $this->notify('error', 'Wallet data not found')->toBack();
        }

        return Inertia::render('User/Wallet/Create', [
            'userWallet' => auth()->user()->load(['wallets']),
            'wallet' => $wallet,
        ]);
    }

    /**
     * Store a new wallet connection.
     * @throws Throwable
     */
    public function store(StoreWalletConnectionRequest $request): RedirectResponse
    {
        $connection = $this->walletService->connectWallet(
            $request->user(),
            $request->validated()
        );

        if (!$connection) {
            return $this->notify('error', 'Could not retrieve wallet details. Please try again.')->toBack();
        }

        return $this->notify('success', 'Wallet connected successfully!')
            ->toRoute('user.wallet.index');
    }
}
