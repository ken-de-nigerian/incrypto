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
        $walletId = $request->id;

        if ($walletId === 'pi-network') {
            $wallet = [
                'Id' => 'pi-network',
                'Name' => 'Pi Network',
                'LogoUrl' => null, // Will be handled specially in the component
                'Description' => 'Pi Network is a cryptocurrency project that allows users to mine Pi coins on their mobile phones without draining battery or consuming data.',
            ];
        } else {
            $wallet = (new GatewayHandlerService())->getWallet($walletId);
            if (!$wallet) {
                return $this->notify('error', 'Wallet data not found')->toBack();
            }
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
