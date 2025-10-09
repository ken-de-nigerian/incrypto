<?php

namespace App\Http\Controllers\User;

use App\Events\CryptoSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSendCryptoRequest;
use App\Models\SendCrypto;
use App\Services\SendCryptoPageService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ManageUserSendCryptoController extends Controller
{
    public SendCryptoPageService $SendCrypto;

    public function __construct(SendCryptoPageService $SendCryptoPageService)
    {
        $this->SendCrypto = $SendCryptoPageService;
    }

    /**
     * Display the Send crypto page.
     */
    public function index(): Response
    {
        // Delegate all data gathering to the service class
        $pageData = $this->SendCrypto->getData(Auth::user());

        return Inertia::render('User/Send', $pageData);
    }

    /**
     * Store the new sent crypto transaction.
     */
    public function store(StoreSendCryptoRequest $request)
    {
        $validatedData = $request->validated();

        $sendCrypto = SendCrypto::create([
            'user_id' => auth()->id(),
            'token_symbol' => $validatedData['token']['symbol'],
            'recipient_address' => $validatedData['recipient_address'],
            'amount' => $validatedData['amount'],
            'fee' => $validatedData['fee'],
            'status' => 'pending',
        ]);

        // Dispatch the event with the new transaction data
        event(new CryptoSent($sendCrypto));

        return $sendCrypto;
    }
}
