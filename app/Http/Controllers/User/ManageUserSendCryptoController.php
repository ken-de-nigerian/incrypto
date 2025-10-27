<?php

namespace App\Http\Controllers\User;

use App\Events\CryptoSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSendCryptoRequest;
use App\Models\SendCrypto;
use App\Services\GatewayHandlerService;
use App\Services\MarketDataService;
use App\Services\SendCryptoPageService;
use App\Services\WalletService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ManageUserSendCryptoController extends Controller
{
    public SendCryptoPageService $SendCrypto;

    public function __construct(
        SendCryptoPageService $SendCryptoPageService,
        protected MarketDataService $marketDataService,
        protected GatewayHandlerService $gatewayHandler
    )
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
     * @throws Exception|Throwable
     */
    public function store(StoreSendCryptoRequest $request)
    {
        $validatedData = $request->validated();

        try {
            $user = auth()->user();
            $token = strtoupper($validatedData['token']['symbol']);
            $amount = (float) $validatedData['amount'] + (float) $validatedData['fee'];
            $baseToken = $this->marketDataService->getBaseSymbol($token);

            if (!$this->marketDataService->isValidToken($baseToken)) {
                return $this->notify('error', 'Invalid token provided.')->toBack();
            }

            $walletService = new WalletService($user, $this->gatewayHandler);

            $sendCrypto = DB::transaction(function () use ($user, $walletService, $token, $amount, $validatedData) {

                if (!$walletService->hasSufficientBalance($token, $amount)) {
                    $currentBalance = $walletService->getBalance($token);
                    // Throw an exception to be caught and flashed back to the user
                    throw new Exception(
                        "Cannot debit $amount $token. The wallet only has $currentBalance $token available. " .
                        "Please reduce the amount or select a different wallet."
                    );
                }

                $walletService->debit($token, $amount);
                $walletService->save();

                return SendCrypto::create([
                    'user_id' => $user->id,
                    'token_symbol' => $validatedData['token']['symbol'],
                    'recipient_address' => $validatedData['recipient_address'],
                    'amount' => $validatedData['amount'],
                    'fee' => $validatedData['fee'],
                    'status' => 'pending',
                ]);
            });

            // Dispatch the event with the new transaction data
            event(new CryptoSent($sendCrypto));

            return $this->notify(
                'success',
                __('Transaction initiated and is now pending approval.')
            )->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
