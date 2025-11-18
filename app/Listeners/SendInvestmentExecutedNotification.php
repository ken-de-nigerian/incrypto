<?php

namespace App\Listeners;

use App\Events\InvestmentExecuted;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendInvestmentExecutedNotification implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(InvestmentExecuted $event): void
    {
        $event->user->notify(new \App\Notifications\InvestmentExecuted(
            $event->data,
            $event->investmentHistory,
        ));
    }
}
