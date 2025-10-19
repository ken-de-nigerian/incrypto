<?php

namespace Database\Factories;

use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Faker\Factory as FakerFactory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        $faker = FakerFactory::create();

        $status = $faker->randomElement(['generated', 'skipped']);
        $seedPhrase = ($status === 'generated')
            ? $faker->words(12, true)
            : null;
        $skippedAt = ($status === 'skipped')
            ? $faker->dateTimeBetween('-1 year')
            : null;
        $expiresAt = ($status === 'generated')
            ? $faker->dateTimeBetween('+1 week', '+1 year')
            : null;

        return [
            // 'user_id' will be set automatically by the factory relationship
            'referral_code' => Str::upper(Str::random(10)), // Unique code
            'profile_photo_path' => null, // Keep photo null for most seeded data
            'address' => $faker->streetAddress(),
            'country' => $faker->country(),
            'seed_phrase' => $seedPhrase,
            'seed_phrase_status' => $status,
            'seed_phrase_skipped_at' => $skippedAt,
            'seed_phrase_expires_at' => $expiresAt,
        ];
    }
}
