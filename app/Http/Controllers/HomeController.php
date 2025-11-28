<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactUsRequest;
use App\Mail\ContactUsEmail;
use App\Services\GatewayHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function index(Request $request)
    {
        return Inertia::render('Home', [
            'cryptos' => Inertia::defer(fn () => $this->getCryptoPrices()),
        ]);
    }

    public function contact(ContactUsRequest $request)
    {
        Mail::mailer(config('settings.email_provider'))
            ->to(config('settings.site.site_email'))
            ->send(new ContactUsEmail($request->validated()));
    }

    /**
     * @return array
     */
    private function getCryptoPrices(): array
    {
        $cryptocurrencies = (new GatewayHandlerService())->fetchGatewaysCrypto();
        $prices = [];

        foreach ($cryptocurrencies as $crypto) {
            $symbol = strtolower($crypto['coin']['symbol']);
            $prices[$symbol] = [
                'name' => $crypto['coin']['name'],
                'image' => $crypto['coin']['image'],
                'current_price' => $crypto['coin']['current_price'],
                'price_change_24h' => $crypto['coin']['price_change_24h'] ?? 0,
                'price_change_percentage_24h' => $crypto['coin']['price_change_percentage_24h'] ?? 0,
            ];
        }

        return $prices;
    }
}
