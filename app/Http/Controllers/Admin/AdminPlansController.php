<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\PlanTimeSetting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Throwable;

class AdminPlansController extends Controller
{
    public function index(Request $request)
    {
        $query = Plan::with('plan_time_settings');

        // Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('interest', 'like', "%$search%")
                    ->orWhere('minimum', 'like', "%$search%")
                    ->orWhere('maximum', 'like', "%$search%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Time setting filter
        if ($request->filled('time_setting')) {
            $query->where('plan_time_settings_id', $request->input('time_setting'));
        }

        // Statistics
        $statistics = [
            'total' => Plan::count(),
            'active' => Plan::where('status', 'active')->count(),
            'inactive' => Plan::where('status', 'inactive')->count(),
        ];

        $pageData['plans'] = $query->paginate(9)->withQueryString();
        $pageData['plan_time_settings'] = PlanTimeSetting::all();
        $pageData['statistics'] = $statistics;
        $pageData['filters'] = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'time_setting' => $request->input('time_setting'),
        ];

        return Inertia::render('Admin/Plans', $pageData);
    }

    /**
     * @throws Throwable
     */
    public function store(StorePlanRequest $request)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated) {
                $validated['period'] = PlanTimeSetting::where('id', $validated['plan_time_settings_id'])
                    ->value('period');
                Plan::create($validated);
            });

            return $this->notify('success', 'Plan created successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function update(UpdatePlanRequest $request, Plan $plan)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $plan) {
                $validated['period'] = PlanTimeSetting::where('id', $validated['plan_time_settings_id'])
                    ->value('period');
                $plan->update($validated);
            });

            return $this->notify('success', 'Plan updated successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function destroy(Plan $plan)
    {
        try {
            DB::transaction(function () use ($plan) {
                $plan->delete();
            });

            return $this->notify('success', 'Plan deleted successfully.')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
