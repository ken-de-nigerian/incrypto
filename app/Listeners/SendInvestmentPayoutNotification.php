<?php

namespace App\Listeners;

use App\Events\InvestmentPayout;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvestmentPayoutNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(InvestmentPayout $event): void
    {
        $event->user->notify(new \App\Notifications\InvestmentPayout(
            $event->investment,
            $event->payoutData,
        ));
    }
}
