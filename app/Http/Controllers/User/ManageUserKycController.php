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
        $kyc = $user->kyc;
        $status = $kyc->status ?? 'unverified';

        // Prepare the base data array for each status
        $statusData = [
            'pending' => [
                'status' => 'pending',
                'title' => 'Verification in Progress',
                'staticMessage' => 'Your documents have been received and are now being reviewed by our compliance team.',
                'dynamicMessage' => 'We appreciate your patience during this process.',
                'action' => [
                    'text' => 'Back to Dashboard',
                    'href' => route('user.dashboard')
                ]
            ],
            'verified' => [
                'status' => 'verified',
                'title' => 'Verification Complete',
                'staticMessage' => 'Congratulations! Your account is now fully verified and you have access to all platform features.',
                'dynamicMessage' => 'Your identity has been successfully confirmed.',
                'action' => [
                    'text' => 'Go to Dashboard',
                    'href' => route('user.dashboard')
                ]
            ],
            'rejected' => [
                'status' => 'rejected',
                'title' => 'Verification Rejected',
                'staticMessage' => 'We were unable to verify your identity with the documents provided.',
                'dynamicMessage' => 'Please review the reason provided below and resubmit the required documents.',
                'action' => [
                    'text' => 'Resubmit Documents',
                    'href' => $kyc ? route('user.kyc.edit', $kyc->id) : '#'
                ]
            ],
            'unverified' => [
                'status' => 'unverified',
                'title' => 'Account Verification Required',
                'staticMessage' => 'To protect against fraudulent activity, please complete identity verification (KYC/AML).',
                'dynamicMessage' => 'Completing verification unlocks all features and enhances your account security.',
                'action' => [
                    'text' => 'Start Verification',
                    'href' => route('user.kyc.create')
                ]
            ]
        ];

        $currentStatusData = $statusData[$status] ?? $statusData['unverified'];

        $dynamicData = [];
        if ($kyc) {
            $dynamicData = [
                'documentTypes' => array_filter([$kyc->id_proof_type, $kyc->address_proof_type]),
                'submissionId' => 'KYC-' . str_pad($kyc->id, 4, '0', STR_PAD_LEFT) . '-' . strtoupper(substr(md5($kyc->id), 0, 4)),
                'submittedAt' => $kyc->created_at?->format('M d, Y, h:i A'),
                'reviewedAt' => $kyc->updated_at?->format('M d, Y, h:i A'),
                'rejectionReason' => $kyc->rejection_reason ?? '',
                'estimatedReviewTime' => '24-48 hours',
            ];
        }

        // Merge the base data with the dynamic data
        $finalKycData = array_merge($currentStatusData, $dynamicData);

        // Pass the single, complete 'kycData' object to the view
        return Inertia::render('User/Kyc', [
            'kycData' => $finalKycData
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user()->load('kyc');
        $kyc = $user->kyc;
        if ($kyc) {
            return redirect()->route('user.kyc.index');
        }
        return Inertia::render('User/Kyc/Create');
    }

    /**
     * @throws Throwable
     */
    public function store(StoreKycRequest $request, KycService $kycService)
    {
        try {
            $kycService->submitKyc($request->user(), $request->validated());
            return $this->notify('success', 'Your KYC information has been submitted for review.')
                ->toRoute('user.kyc.index');
        } catch (Exception $e) {
            Log::error('KYC Submission failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return $this->notify('error', 'There was a problem submitting your information. Please try again.')->toBack();
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
            return $this->notify('success', 'Your KYC information has been resubmitted for review.')
                ->toRoute('user.kyc.index');
        } catch (Exception $e) {
            Log::error('KYC Update failed', ['submission_id' => $submission->id, 'error' => $e->getMessage()]);
            return $this->notify('error', 'There was a problem updating your information. Please try again.')->toBack();
        }
    }
}
