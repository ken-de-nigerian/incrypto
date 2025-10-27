<?php

namespace App\Http\Controllers\User;

use App\Events\CryptoReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceivedCryptoRequest;
use App\Models\ReceivedCrypto;
use App\Services\ReceiveCryptoPageService;
use Illuminate\Support\Facades\Auth;
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
    public function store(StoreReceivedCryptoRequest $request)
    {
        $validated = $request->validated();

        $receivedCrypto = ReceivedCrypto::updateOrCreate(
            [
                'user_id'        => auth()->id(),
                'token_symbol'   => $validated['token_symbol'],
                'wallet_address' => $validated['wallet_address'],
                'status'         => 'pending',
            ], []
        );

        // Dispatch the event with the new transaction data
        event(new CryptoReceived($receivedCrypto));

        return $this->notify(
            'success',
            __('Transaction record created. We will monitor the deposit for confirmation.')
        )->toBack();
    }
}
