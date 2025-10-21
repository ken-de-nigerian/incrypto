<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectUserKycRequest;
use App\Models\KycSubmission;
use App\Services\ApproveUserKycService;
use App\Services\RejectUserKycService;
use Exception;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Throwable;

class AdminKycController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status');
        $search = $request->input('search');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');

        $kycQuery = KycSubmission::query()
            ->with('user.profile');

        if ($status) {
            $kycQuery->where('status', $status);
        }

        if ($search) {
            $kycQuery->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%$search%")
                    ->orWhere('last_name', 'like', "%$search%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%$search%");
                    });
            });
        }

        switch ($sortBy) {
            case 'name':
                $kycQuery->orderBy('first_name', $sortOrder);
                break;
            case 'status':
                $kycQuery->orderBy('status', $sortOrder);
                break;
            case 'date':
            default:
                $kycQuery->orderBy('created_at', $sortOrder === 'asc' ? 'asc' : 'desc');
                break;
        }

        $users = $kycQuery->paginate(10)->withQueryString()
            ->through(function ($kycSubmission) {
                return $kycSubmission->append([
                    'id_front_proof_url',
                    'id_back_proof_url',
                    'address_front_proof_url',
                ]);
            });

        return Inertia::render('Admin/Kyc/Index', [
            'metrics' => [
                'kyc_unverified' => KycSubmission::where('status', 'pending')->count(),
                'kyc_rejected' => KycSubmission::where('status', 'rejected')->count(),
            ],
            'users' => $users,
            'filters' => [
                'status' => $status,
            ]
        ]);
    }

    /**
     * @throws Throwable
     */
    public function approve(Request $request, KycSubmission $kyc, ApproveUserKycService $approveUserKycService)
    {
        if ($kyc->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending KYC submissions can be approved.');
        }

        try {
            $approveUserKycService->approve(
                $kyc
            );
            return back()->with('success', 'KYC submission approved successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }

    /**
     * @throws Throwable
     */
    public function reject(RejectUserKycRequest $request, KycSubmission $kyc, RejectUserKycService $rejectUserKycService)
    {
        if ($kyc->status !== 'pending') {
            return redirect()->back()->with('error', 'Only pending KYC submissions can be rejected.');
        }

        try {
            $rejectUserKycService->reject(
                $kyc,
                $request->validated()
            );
            return back()->with('success', 'KYC submission rejected successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', __($e->getMessage()));
        }
    }
}
