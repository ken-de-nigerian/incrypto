<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB; // <-- ADDED: Import DB Facade
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class ProfileService
{
    /**
     * Update the User and UserProfile models, handling avatar upload.
     * @throws Throwable
     */
    public function update(User $user, array $data, ?UploadedFile $avatar = null): void
    {
        $profilePhotoPath = null;

        if ($avatar) {
            $profilePhotoPath = $this->storeAvatar($user, $avatar);
        }

        DB::transaction(function () use ($user, $data, $profilePhotoPath) {

            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone_number' => $data['phone_number'],
            ]);

            $profileData = [
                'address' => $data['address'],
                'country' => $data['country'],
            ];

            if ($profilePhotoPath) {
                $profileData['profile_photo_path'] = $profilePhotoPath;
            }

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );
        });
    }

    private function storeAvatar(User $user, UploadedFile $avatar): string
    {
        if ($user->profile?->profile_photo_path) {
            $oldPath = str_replace(asset('storage/'), '', $user->profile->profile_photo_path);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $avatar->store('avatars', 'public');

        $image = Image::read(storage_path('app/public/' . $path));
        $image->resize(124, 124);
        $image->save();

        return asset('storage/' . $path);
    }
}
