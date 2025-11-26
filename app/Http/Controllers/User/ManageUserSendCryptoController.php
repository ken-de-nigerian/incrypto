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
        $pageData['networkFee'] = auth()->user()->profile->network_fee;
        $pageData['chargeNetworkFee'] = auth()->user()->profile->charge_network_fee;
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
            $amount = (float) $validatedData['amount'];
            $fee = (float) $validatedData['fee'];
            $baseToken = $this->marketDataService->getBaseSymbol($token);

            if (!$this->marketDataService->isValidToken($baseToken)) {
                return $this->notify('error', 'Invalid token provided.')->toBack();
            }

            $walletService = new WalletService($user, $this->gatewayHandler);

            $sendCrypto = DB::transaction(function () use ($user, $walletService, $token, $amount, $fee, $validatedData) {

                $isSendingETH = $token === 'ETH';

                // If sending ETH, check if balance is sufficient for amount + fee
                if ($isSendingETH) {
                    $totalAmount = $amount + $fee;
                    if (!$walletService->hasSufficientBalance($token, $totalAmount)) {
                        $currentBalance = $walletService->getBalance($token);
                        throw new Exception(
                            "Cannot debit $totalAmount $token (amount + fee). The wallet only has $currentBalance $token available. " .
                            "Please reduce the amount or select a different wallet."
                        );
                    }

                    // Debit the total amount (amount + fee) from ETH wallet
                    $walletService->debit($token, $totalAmount);
                } else {
                    // If sending other tokens, check token balance and ETH balance separately
                    if (!$walletService->hasSufficientBalance($token, $amount)) {
                        $currentBalance = $walletService->getBalance($token);
                        throw new Exception(
                            "Cannot debit $amount $token. The wallet only has $currentBalance $token available. " .
                            "Please reduce the amount or select a different wallet."
                        );
                    }

                    // Check if user has sufficient ETH for network fee
                    if (!$walletService->hasSufficientBalance('ETH', $fee)) {
                        $currentETHBalance = $walletService->getBalance('ETH');
                        throw new Exception(
                            "Insufficient ETH balance for network fee. Required: $fee ETH, Available: $currentETHBalance ETH. " .
                            "Please add more ETH to your wallet."
                        );
                    }

                    // Debit the token amount from the selected token wallet
                    $walletService->debit($token, $amount);

                    // Debit the network fee from ETH wallet
                    $walletService->debit('ETH', $fee);
                }

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
