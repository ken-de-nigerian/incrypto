<?php

namespace App\Services;

use App\Events\KycSubmitted;
use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class KycService
{
    /**
     * @throws Throwable
     */
    public function submitKyc(User $user, array $data): KycSubmission
    {
        $submission = DB::transaction(function () use ($user, $data) {

            $idFrontPath = $this->storeDocument($data['id_front_proof']);
            $idBackPath = isset($data['id_back_proof']) ? $this->storeDocument($data['id_back_proof']) : null;
            $addressFrontPath = $this->storeDocument($data['address_front_proof']);

            return KycSubmission::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'],
                'date_of_birth' => $data['date_of_birth'],
                'country' => $data['country'],
                'state' => $data['state'],
                'city' => $data['city'],
                'address' => $data['address'],
                'id_proof_type' => $data['id_proof_type'],
                'id_front_proof_path' => $idFrontPath,
                'id_back_proof_path' => $idBackPath,
                'address_proof_type' => $data['address_proof_type'],
                'address_front_proof_path' => $addressFrontPath,
            ]);
        });

        // Dispatch the event after the transaction is successful
        event(new KycSubmitted($submission));

        return $submission;
    }

    /**
     * @throws Throwable
     */
    public function updateKyc(KycSubmission $submission, array $data): KycSubmission
    {
        return DB::transaction(function () use ($submission, $data) {

            $updateData = $data;

            if (!empty($data['id_front_proof'])) {
                $this->deleteDocument($submission->id_front_proof_path);
                $updateData['id_front_proof_path'] = $this->storeDocument($data['id_front_proof']);
            }

            if (!empty($data['id_back_proof'])) {
                $this->deleteDocument($submission->id_back_proof_path);
                $updateData['id_back_proof_path'] = $this->storeDocument($data['id_back_proof']);
            }

            if (!empty($data['address_front_proof'])) {
                $this->deleteDocument($submission->address_front_proof_path);
                $updateData['address_front_proof_path'] = $this->storeDocument($data['address_front_proof']);
            }

            // Reset status to 'pending' for re-review and update the record
            $updateData['status'] = 'pending';
            $submission->update($updateData);

            return $submission;
        });
    }

    private function storeDocument(UploadedFile $file): string
    {
        return $file->store('kyc_documents', 'public');
    }

    /**
     * Delete a document from storage if it exists.
     */
    private function deleteDocument(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
