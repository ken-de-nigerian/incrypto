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
        $user = Auth::user();
        $pageData = $this->SendCrypto->getData($user);
        $pageData['networkFee'] = (float) $user->profile->network_fee;
        $pageData['chargeNetworkFee'] = (bool) $user->profile->charge_network_fee;

        // Network fee mapping
        $pageData['networkFeeMap'] = [
            'ETH' => ['symbol' => 'ETH', 'name' => 'Ethereum'],
            'USDT_ERC20' => ['symbol' => 'ETH', 'name' => 'Ethereum'],
            'USDT_TRC20' => ['symbol' => 'TRX', 'name' => 'Tron'],
            'USDT_BEP20' => ['symbol' => 'BNB', 'name' => 'Binance Smart Chain'],
            'BTC' => ['symbol' => 'BTC', 'name' => 'Bitcoin'],
            'BNB' => ['symbol' => 'BNB', 'name' => 'Binance Smart Chain'],
            'TRX' => ['symbol' => 'TRX', 'name' => 'Tron'],
        ];

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
            $feeToken = $validatedData['fee_token'] ?? 'ETH';
            $baseToken = $this->marketDataService->getBaseSymbol($token);

            if (!$this->marketDataService->isValidToken($baseToken)) {
                return $this->notify('error', 'Invalid token provided.')->toBack();
            }

            $walletService = new WalletService($user, $this->gatewayHandler);

            $sendCrypto = DB::transaction(function () use ($user, $walletService, $token, $amount, $fee, $feeToken, $validatedData) {

                $isSendingSameAsFeeCurrency = $token === $feeToken;

                if ($isSendingSameAsFeeCurrency) {
                    // If sending token matches fee token, combine them
                    $totalAmount = $amount + $fee;
                    if (!$walletService->hasSufficientBalance($token, $totalAmount)) {
                        $currentBalance = $walletService->getBalance($token);
                        throw new Exception(
                            "Cannot debit $totalAmount $token (amount + fee). The wallet only has $currentBalance $token available. " .
                            "Please reduce the amount or select a different wallet."
                        );
                    }

                    $walletService->debit($token, $totalAmount);
                } else {
                    // Check token balance
                    if (!$walletService->hasSufficientBalance($token, $amount)) {
                        $currentBalance = $walletService->getBalance($token);
                        throw new Exception(
                            "Cannot debit $amount $token. The wallet only has $currentBalance $token available. " .
                            "Please reduce the amount or select a different wallet."
                        );
                    }

                    // Check if user has sufficient balance for network fee in the fee token
                    if (!$walletService->hasSufficientBalance($feeToken, $fee)) {
                        $currentFeeBalance = $walletService->getBalance($feeToken);
                        throw new Exception(
                            "Insufficient $feeToken balance for network fee. Required: $fee $feeToken, Available: $currentFeeBalance $feeToken. " .
                            "Please add more $feeToken to your wallet."
                        );
                    }

                    // Debit the token amount
                    $walletService->debit($token, $amount);

                    // Debit the network fee from fee token wallet
                    $walletService->debit($feeToken, $fee);
                }

                $walletService->save();

                return SendCrypto::create([
                    'user_id' => $user->id,
                    'token_symbol' => $validatedData['token']['symbol'],
                    'recipient_address' => $validatedData['recipient_address'],
                    'amount' => $validatedData['amount'],
                    'fee' => $validatedData['fee'],
                    'fee_token' => $feeToken,
                    'status' => 'pending',
                ]);
            });

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
