<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanTimeSettingRequest;
use App\Http\Requests\UpdatePlanTimeSettingRequest;
use App\Models\PlanTimeSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class AdminPlanTimeSettingsController extends Controller
{
    public function index(Request $request)
    {
        $query = PlanTimeSetting::withCount('plans');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('period', 'like', "%$search%");
            });
        }

        // Statistics
        $statistics = [
            'total' => PlanTimeSetting::count(),
            'in_use' => PlanTimeSetting::has('plans')->count(),
            'unused' => PlanTimeSetting::doesntHave('plans')->count(),
        ];

        $pageData['time_settings'] = $query->paginate(12)->withQueryString();
        $pageData['statistics'] = $statistics;
        $pageData['filters'] = [
            'search' => $request->input('search'),
        ];

        return Inertia::render('Admin/PlanTimeSettings', $pageData);
    }

    /**
     * @throws Throwable
     */
    public function store(StorePlanTimeSettingRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                PlanTimeSetting::create($validated);
            });

            return $this->notify('success', 'Time setting created successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function update(UpdatePlanTimeSettingRequest $request, PlanTimeSetting $planTimeSetting)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $planTimeSetting) {
                $planTimeSetting->update($validated);
            });

            return $this->notify('success', 'Time setting updated successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(PlanTimeSetting $planTimeSetting)
    {
        try {

            // Check if any plans are using this time setting
            if ($planTimeSetting->plans()->count() > 0) {
                return $this->notify('error', 'Cannot delete time setting that is being used by plans.')
                    ->toBack();

            }

            DB::transaction(function () use ($planTimeSetting) {
                $planTimeSetting->delete();
            });

            return $this->notify('success', 'Time setting deleted successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
