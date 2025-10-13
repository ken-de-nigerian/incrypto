<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProfileService
{
    public function update(User $user, array $data, ?UploadedFile $avatar = null): void
    {
        $user->update([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
        ]);

        $profileData = [
            'address' => $data['address'],
            'country' => $data['country'],
        ];

        if ($avatar) {
            $profileData['profile_photo_path'] = $this->storeAvatar($user, $avatar);
        }

        UserProfile::updateOrCreate(['user_id' => $user->id], $profileData);
    }

    private function storeAvatar(User $user, UploadedFile $avatar): string
    {
        // Delete old avatar if it exists
        if ($user->profile?->profile_photo_path) {
            Storage::disk('public')->delete($user->profile->profile_photo_path);
        }

        $path = $avatar->store('avatars', 'public');

        // Resize the image
        $image = Image::read(storage_path('app/public/' . $path));
        $image->resize(124, 124);
        $image->save();

        return asset('storage/' . $path);
    }
}
