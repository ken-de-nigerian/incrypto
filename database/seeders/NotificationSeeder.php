<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::all();

        // Define possible notification types, titles, and content
        $notificationTypes = [
            'transaction_received' => [
                'titles' => [
                    'New Crypto Received!',
                    'Incoming Transaction Alert',
                    'Funds Deposited to Your Wallet',
                ],
                'data' => function () use ($faker) {
                    $cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'XRP'];
                    $crypto = $faker->randomElement($cryptos);
                    $amount = $faker->randomFloat(4, 0.001, 5);
                    return [
                        'amount' => $amount,
                        'crypto' => $crypto,
                        'from' => $faker->bothify('0x##??##??##??##??##'),
                        'content' => "You received $amount $crypto from an external wallet.",
                    ];
                },
            ],
            'transaction_sent' => [
                'titles' => [
                    'Transaction Sent Successfully',
                    'Crypto Transfer Completed',
                    'Funds Sent from Your Wallet',
                ],
                'data' => function () use ($faker) {
                    $cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'XRP'];
                    $crypto = $faker->randomElement($cryptos);
                    $amount = $faker->randomFloat(4, 0.001, 5);
                    return [
                        'amount' => $amount,
                        'crypto' => $crypto,
                        'to' => $faker->bothify('0x##??##??##??##??##'),
                        'content' => "You sent $amount $crypto to an external wallet.",
                    ];
                },
            ],
            'price_alert' => [
                'titles' => [
                    'Crypto Price Alert!',
                    'Market Update: Price Change',
                    'Price Threshold Reached',
                ],
                'data' => function () use ($faker) {
                    $cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'XRP'];
                    $crypto = $faker->randomElement($cryptos);
                    $price = $faker->randomFloat(2, 100, 50000);
                    $direction = $faker->randomElement(['above', 'below']);
                    return [
                        'crypto' => $crypto,
                        'price' => $price,
                        'direction' => $direction,
                        'content' => "$crypto price moved $direction $price.",
                    ];
                },
            ],
            'wallet_update' => [
                'titles' => [
                    'Wallet Security Update',
                    'New Feature in Your Wallet',
                    'Wallet Maintenance Notification',
                ],
                'data' => function () use ($faker) {
                    $message = $faker->sentence();
                    return [
                        'content' => $message,
                    ];
                },
            ],
        ];

        foreach ($users as $user) {
            // Generate 15-20 notifications per user
            $notificationCount = rand(15, 20);

            for ($i = 0; $i < $notificationCount; $i++) {
                $type = array_keys($notificationTypes)[array_rand(array_keys($notificationTypes))];
                $title = $faker->randomElement($notificationTypes[$type]['titles']);

                DB::table('notifications')->insert([
                    'id' => Str::uuid(),
                    'type' => $type,
                    'notifiable_type' => 'App\\Models\\User',
                    'notifiable_id' => $user->id,
                    'data' => json_encode(array_merge(
                        ['title' => $title],
                        $notificationTypes[$type]['data']()
                    )),
                    'read_at' => $faker->optional(0.3)->dateTimeThisYear(),
                    'created_at' => $faker->dateTimeThisYear(),
                    'updated_at' => $faker->dateTimeThisYear(),
                ]);
            }
        }
    }
}
