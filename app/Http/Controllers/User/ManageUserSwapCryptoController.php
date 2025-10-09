<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveTokenRequest;
use App\Http\Requests\ProcessSwapRequest;
use App\Services\CryptoSwapService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Throwable;

class ManageUserSwapCryptoController extends Controller
{
    protected CryptoSwapService $cryptoSwapService;

    public function __construct(CryptoSwapService $cryptoSwapService)
    {
        $this->cryptoSwapService = $cryptoSwapService;
    }

    /**
     * Display the swap page.
     */
    public function index()
    {
        $swapData = $this->cryptoSwapService->getSwapPageData(Auth::user());
        return Inertia::render('User/Swap', $swapData);
    }

    /**
     * Approve token for swapping.
     * @throws Exception
     */
    public function approve(ApproveTokenRequest $request)
    {
        $this->cryptoSwapService->approveToken(
            $request->validated()
        );

        return response()->json(['message' => 'Token approved successfully.']);
    }

    /**
     * Process the swap.
     *
     * @throws Throwable
     */
    public function process(ProcessSwapRequest $request)
    {
        try {

            $result = $this->cryptoSwapService->executeSwap(
                Auth::user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Swap successful!',
                'transactionHash' => $result->transaction_hash,
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
