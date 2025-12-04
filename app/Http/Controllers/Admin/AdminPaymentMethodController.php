<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentGatewayRequest;
use App\Http\Requests\UpdatePaymentGatewayRequest;
use App\Models\WalletAddress;
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
            // Start with query builder instead of getting all data
            $query = WalletAddress::query();

            // Apply search filter
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('abbreviation', 'like', "%$search%");
                });
            }

            // Apply status filter
            if ($status !== null && $status !== '') {
                $statusMap = [
                    'active' => 1,
                    'deactivated' => 0,
                    '1' => 1,
                    '0' => 0
                ];

                $statusValue = $statusMap[$status] ?? null;

                if ($statusValue !== null) {
                    $query->where('status', $statusValue);
                }
            }

            // Order by name
            $query->orderBy('name');

            // Paginate
            $perPage = 12;
            $wallets = $query->paginate($perPage);

            // Fetch cryptos and create lookup map
            $gatewayService = new GatewayHandlerService();
            $cryptos = $gatewayService->getCryptos();
            $cryptoMap = collect($cryptos)->keyBy('id')->all();

            // Transform the paginated data
            $wallets->getCollection()->transform(function ($wallet) use ($cryptoMap) {
                $walletArray = [
                    'method_code' => $wallet->method_code,
                    'name' => $wallet->name,
                    'abbreviation' => $wallet->abbreviation,
                    'gateway_parameter' => $wallet->gateway_parameter,
                    'status' => (string) $wallet->status,
                    'coingecko_id' => $wallet->coingecko_id,
                    'image' => null,
                ];

                // Add crypto image if available
                $coinId = $wallet->coingecko_id;
                if ($coinId && isset($cryptoMap[$coinId])) {
                    $walletArray['image'] = $cryptoMap[$coinId]['image'];
                }

                return $walletArray;
            });

            return $wallets;

        } catch (Throwable $e) {
            Log::error('Error in getGateways(): ' . $e->getMessage());
            return new LengthAwarePaginator([], 0, 12, 1, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        }
    }

    private function getMetrics(): array
    {
        try {
            $total = WalletAddress::count();
            $active = WalletAddress::where('status', 1)->count();
            $deactivated = WalletAddress::where('status', 0)->count();

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
            return $this->notify('success', __('Payment method created successfully!'))->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
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
            return $this->notify('success', __('Payment method updated successfully!'))->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * Delete a payment method
     *
     * @throws Throwable
     */
    public function destroy(string $methodCode, StorePaymentGatewayService $paymentGatewayService)
    {
        try {
            $paymentGatewayService->delete($methodCode);
            return $this->notify('success', __('Payment method deleted successfully!'))->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
