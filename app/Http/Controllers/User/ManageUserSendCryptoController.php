<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SendCrypto;
use App\Services\SendCryptoPageService;
use Illuminate\Http\Request;
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'token_symbol' => 'required|string',
            'recipient_address' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'fee' => 'required|numeric|min:0',
            'speed' => 'required|in:slow,average,fast',
        ]);

        SendCrypto::create([
            'user_id' => auth()->id(),
            'token_symbol' => $validated['token_symbol'],
            'recipient_address' => $validated['recipient_address'],
            'amount' => $validated['amount'],
            'fee' => $validated['fee'],
            'status' => 'pending',
        ]);

        return back();
    }
}
