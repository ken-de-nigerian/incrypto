<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceivedCryptoRequest;
use App\Models\ReceivedCrypto;
use App\Models\User;
use App\Notifications\NewReceivedCryptoTransactionAdminNotify;
use App\Services\ReceiveCryptoPageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;
use Inertia\Response;

class ManageUserReceiveCryptoController extends Controller
{
    public ReceiveCryptoPageService $receiveCrypto;

    public function __construct(ReceiveCryptoPageService $receiveCryptoPageService)
    {
        $this->receiveCrypto = $receiveCryptoPageService;
    }

    /**
     * Display the receive crypto page.
     */
    public function index(): Response
    {
        // Delegate all data gathering to the service class
        $pageData = $this->receiveCrypto->getData(Auth::user());

        return Inertia::render('User/Receive', $pageData);
    }

    /**
     * Store a new pending transaction record.
     */
    public function store(StoreReceivedCryptoRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $receivedCrypto = ReceivedCrypto::updateOrCreate(
            [
                'user_id'        => auth()->id(),
                'token_symbol'   => $validated['token_symbol'],
                'wallet_address' => $validated['wallet_address'],
                'status'         => 'pending',
            ],
            []
        );

        // Check if the model was recently created to avoid sending duplicate emails
        if ($receivedCrypto->wasRecentlyCreated) {
            $admins = User::where('role', 'admin')->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewReceivedCryptoTransactionAdminNotify($receivedCrypto));
            }
        }

        return back();
    }
}
