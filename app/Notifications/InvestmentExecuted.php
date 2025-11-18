<?php

namespace App\Notifications;

use App\Models\InvestmentHistory;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentExecuted extends Notification
{
    use Queueable;

    public array $data;
    public InvestmentHistory $investmentHistory;

    /**
     * Create a new notification instance.
     */
    public function __construct(array $data, InvestmentHistory $investmentHistory)
    {
        $this->data = $data;
        $this->investmentHistory = $investmentHistory;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Investment Executed Successfully - ' . config('app.name'))
            ->view('emails.investment_executed', [
                'user' => $notifiable,
                'data' => $this->data,
                'investmentHistory' => $this->investmentHistory->load('plan.plan_time_settings'),
                'nextPayoutTime' => Carbon::parse($this->investmentHistory->next_time)->format('M d, Y \a\t h:i A')
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'investment_executed',
            'title' => 'Investment Executed Successfully',
            'investment_id' => $this->investmentHistory->id,
            'plan_name' => $this->investmentHistory->plan->name ?? 'Investment Plan',
            'amount' => $this->investmentHistory->amount,
            'interest_rate' => $this->investmentHistory->interest,
            'period' => $this->investmentHistory->period,
            'next_payout' => $this->investmentHistory->next_time,
            'status' => $this->investmentHistory->status,
            'content' => 'Your investment of $' . number_format($this->investmentHistory->amount, 2) . ' has been successfully executed.',
        ];
    }
}
