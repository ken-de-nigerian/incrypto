<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\GatewayHandlerService;
use Illuminate\Http\Request;

class ChartDataController extends Controller
{
    protected GatewayHandlerService $gatewayHandler;

    public function __construct(GatewayHandlerService $gatewayHandler)
    {
        $this->gatewayHandler = $gatewayHandler;
    }

    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string',
            'days' => 'required|numeric|min:0.01|max:365'
        ]);

        $symbol = $this->cleanSymbol($validated['symbol']);
        $days = $validated['days'];
        $result = $this->gatewayHandler->fetchChartData($symbol, $days);

        if ($result['success'] ?? false) {
            return response()->json($result['data']);
        }

        return response()->json([
            'error' => $result['error'] ?? 'Failed to fetch chart data',
            'message' => $result['message'] ?? 'Unknown error occurred'
        ], $result['status'] ?? 500);
    }

    /**
     * Cleans the symbol by stripping the 'usdt_' prefix if present.
     * * @param string $symbol
     * @return string
     */
    public function cleanSymbol(string $symbol): string
    {
        if (str_starts_with(strtolower($symbol), 'usdt_')) {
            return 'tether';
        }

        return $symbol;
    }
}
