<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveLoanRequest;
use App\Http\Requests\RejectLoanRequest;
use App\Models\Loan;
use App\Services\TradeService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class AdminLoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with('user');

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhereHas('user', function($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%$search%")
                            ->orWhere('last_name', 'like', "%$search%")
                            ->orWhere('email', 'like', "%$search%");
                    });
            });
        }

        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Amount range filter
        if ($request->filled('amount_from')) {
            $query->where('loan_amount', '>=', $request->amount_from);
        }

        if ($request->filled('amount_to')) {
            $query->where('loan_amount', '<=', $request->amount_to);
        }

        // Sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Fetch loans with pagination
        $pageData['loans'] = $query->paginate(10)->withQueryString();

        // Calculate Stats
        $pageData['stats'] = [
            'total_borrowed' => Loan::whereIn('status', ['approved', 'completed'])->sum('loan_amount'),
            'active_loans' => Loan::where('status', 'approved')->count(),
            'total_repaid' => Loan::where('status', 'completed')->sum('total_payment'),
            'pending_requests' => Loan::where('status', 'pending')->count(),
        ];

        // Pass filters back to the view
        $pageData['filters'] = [
            'search' => $request->search,
            'status' => $request->status ?? 'all',
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'amount_from' => $request->amount_from,
            'amount_to' => $request->amount_to,
            'sort_by' => $sortField,
            'sort_order' => $sortOrder,
        ];

        return Inertia::render('Admin/Loans', $pageData);
    }

    /**
     * @throws Throwable
     */
    public function approve(ApproveLoanRequest $request, Loan $loan, TradeService $tradeService)
    {
        $user = $loan->user;

        try {
            $result = $tradeService->approveLoan(
                $user,
                $loan,
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to approve loan request - invalid state')->toBack();
            }

            return $this->notify('success', 'Loan request approved successfully')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }

    /**
     * @throws Throwable
     */
    public function reject(RejectLoanRequest $request, Loan $loan, TradeService $tradeService)
    {
        $user = $loan->user;

        try {
            $result = $tradeService->rejectLoan(
                $user,
                $loan,
                $request->validated()
            );

            if (!$result) {
                return $this->notify('error', 'Failed to reject loan request - invalid state')->toBack();
            }

            return $this->notify('success', 'Loan request rejected successfully')
                ->toBack();
        } catch (Exception $e) {
            return $this->notify('error', __($e->getMessage()))->toBack();
        }
    }
}
