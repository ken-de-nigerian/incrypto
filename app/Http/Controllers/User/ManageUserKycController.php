<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycRequest;
use App\Http\Requests\UpdateKycRequest;
use App\Models\KycSubmission;
use App\Services\KycService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Throwable;

class ManageUserKycController extends Controller
{
    public function index()
    {
        $user = Auth::user()->load('kyc');
        $status = $user->kyc->status ?? 'unverified';

        // Prepare the data array
        $statusData = [
            'pending' => [
                'status' => 'pending',
                'title' => 'Verification in Progress',
                'staticMessage' => 'To protect against fraudulent activity, all participants will be required to complete identity verification (KYC/AML).',
                'dynamicMessage' => 'Your documents are under review. This typically takes 24 - 48 hours to verify.',
                'action' => [
                    'text' => 'Back to Dashboard',
                    'href' => route('user.dashboard')
                ]
            ],
            'verified' => [
                'status' => 'verified',
                'title' => 'Verification Complete',
                'staticMessage' => 'Your account is now fully verified and you can access all platform features.',
                'dynamicMessage' => 'Your identity has been successfully verified!',
                'action' => [
                    'text' => 'Go to Dashboard',
                    'href' => route('user.dashboard')
                ]
            ],
            'rejected' => [
                'status' => 'rejected',
                'title' => 'Verification Rejected',
                'staticMessage' => 'We couldn\'t verify your identity with the provided documents.',
                'dynamicMessage' => $user->kyc?->rejection_reason ?? 'Documents didn\'t meet requirements. Please try again.',
                'action' => [
                    'text' => 'Resubmit Documents',
                    'href' => route('user.kyc.edit', $user->kyc?->id ?? 0)
                ]
            ],
            'unverified' => [
                'status' => 'unverified',
                'title' => 'Account Verification Required',
                'staticMessage' => 'To protect against fraudulent activity, all participants will be required to complete identity verification (KYC/AML).',
                'dynamicMessage' => 'To access all features, please complete your identity verification.',
                'action' => [
                    'text' => 'Start Verification',
                    'href' => route('user.kyc.create')
                ]
            ]
        ];

        $currentStatusData = $statusData[$status] ?? $statusData['unverified'];

        // Add the contact email
        $currentStatusData['contactEmail'] = config('settings.site.site_email', 'support@example.com');

        // Pass a single 'kycData' object to the view
        return Inertia::render('User/Kyc', [
            'kycData' => $currentStatusData
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('User/Kyc/Create');
    }

    /**
     * @throws Throwable
     */
    public function store(StoreKycRequest $request, KycService $kycService)
    {
        try {

            $kycService->submitKyc($request->user(), $request->validated());

            return redirect()->route('user.kyc.index')
                ->with('success', 'Your KYC information has been submitted for review.');
        } catch (Exception $e) {
            Log::error('KYC Submission failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'There was a problem submitting your information. Please try again.');
        }
    }

    public function edit(KycSubmission $submission)
    {
        return Inertia::render('User/Kyc/Edit', [
            'submission' => $submission->append([
                'id_front_proof_url',
                'id_back_proof_url',
                'address_front_proof_url',
            ]),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateKycRequest $request, KycSubmission $submission, KycService $kycService)
    {
        try {
            $kycService->updateKyc($submission, $request->validated());

            return redirect()->route('user.kyc.index')
                ->with('success', 'Your KYC information has been resubmitted for review.');
        } catch (Exception $e) {
            Log::error('KYC Update failed', ['submission_id' => $submission->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'There was a problem updating your information. Please try again.');
        }
    }
}
