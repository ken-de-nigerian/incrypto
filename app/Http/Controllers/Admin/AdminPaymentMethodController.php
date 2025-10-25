<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentGatewayRequest;
use App\Http\Requests\UpdatePaymentGatewayRequest;
use App\Services\GatewayHandlerService;
use App\Services\StorePaymentGatewayService;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

class AdminPaymentMethodController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|Throwable
     */
    public function index()
    {
        $search = request()->get('search', null);
        $status = request()->get('status', null);

        $gateways = $this->getGateways($search, $status);
        $metrics = $this->getMetrics();
        $cryptos = (new GatewayHandlerService())->getCryptos();

        return Inertia::render('Admin/PaymentMethod', [
            'gateways' => $gateways,
            'cryptos' => $cryptos,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'metrics' => $metrics,
        ]);
    }

    private function getGateways(?string $search = null, ?string $status = null): LengthAwarePaginator
    {
        try {
            $wallets = config('gateways.wallet_addresses');

            if (!is_array($wallets)) {
                return new LengthAwarePaginator([], 0, 10, 1);
            }

            $collection = collect($wallets);
            $statusValueToFilter = null;

            if ($search) {
                $collection = $collection->filter(function ($wallet) use ($search) {
                    return stripos($wallet['name'] ?? '', $search) !== false ||
                        stripos($wallet['abbreviation'] ?? '', $search) !== false;
                });
            }

            if ($status !== null && $status !== '') {
                $statusMap = ['active' => '1', 'deactivated' => '0', '1' => '1', '0' => '0'];
                $statusKey = $status;
                $statusValueToFilter = $statusMap[$statusKey] ?? null;

                if ($statusValueToFilter !== null) {
                    $collection = $collection->filter(function ($wallet) use ($statusValueToFilter) {
                        return $wallet['status'] === $statusValueToFilter;
                    });
                }
            }

            if ($statusValueToFilter === null) {
                $sorted = $collection
                    ->sortBy('name')
                    ->values()
                    ->toArray();
            } else {
                $sorted = $collection->values()->toArray();
            }

            // Fetch cryptos and create lookup map
            $gatewayService = new GatewayHandlerService();
            $cryptos = $gatewayService->getCryptos();
            $cryptoMap = collect($cryptos)
                ->keyBy('id')
                ->all();

            // Merge gateways with crypto data
            $sorted = array_map(function ($gateway) use ($cryptoMap) {
                $coinId = $gateway['coingecko_id'] ?? null;

                if ($coinId && isset($cryptoMap[$coinId])) {
                    $gateway['image'] = $cryptoMap[$coinId]['image'];
                }

                return $gateway;
            }, $sorted);

            $perPage = 12;
            $page = request()->get('page', 1);
            $total = count($sorted);
            $items = array_slice($sorted, ($page - 1) * $perPage, $perPage);

            return new LengthAwarePaginator($items, $total, $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        } catch (Throwable $e) {
            Log::error('Error in getGateways(): ' . $e->getMessage());
            return new LengthAwarePaginator([], 0, 10, 1);
        }
    }

    private function getMetrics(): array
    {
        try {
            $wallets = config('gateways.wallet_addresses');

            if (!is_array($wallets)) {
                return [
                    'total_gateways' => 0,
                    'active_gateways' => 0,
                    'deactivated_gateways' => 0,
                ];
            }

            $collection = collect($wallets);
            $total = $collection->count();
            $active = $collection->where('status', '1')->count();
            $deactivated = $collection->where('status', '0')->count();

            return [
                'total_gateways' => $total,
                'active_gateways' => $active,
                'deactivated_gateways' => $deactivated,
            ];
        } catch (Throwable $e) {
            Log::error('Error in getMetrics(): ' . $e->getMessage());
            return [
                'total_gateways' => 0,
                'active_gateways' => 0,
                'deactivated_gateways' => 0,
            ];
        }
    }

    /**
     * @throws Throwable
     */
    public function store(StorePaymentGatewayRequest $request, StorePaymentGatewayService $storePaymentGatewayService)
    {
        $validated = $request->validated();

        try {
            $storePaymentGatewayService->store($validated);
            return redirect()->back()->with('success', __('Payment method created successfully!'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    /**
     * @throws Throwable
     */
    public function update(UpdatePaymentGatewayRequest $request, StorePaymentGatewayService $updatePaymentGatewayService)
    {
        $validated = $request->validated();

        try {
            $updatePaymentGatewayService->update($validated);
            return redirect()->back()->with('success', __('Payment method updated successfully!'));
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
}
