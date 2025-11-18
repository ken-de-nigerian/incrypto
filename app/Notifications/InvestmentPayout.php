<?php

namespace App\Notifications;

use App\Models\InvestmentHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvestmentPayout extends Notification
{
    use Queueable;

    public InvestmentHistory $investment;
    public array $payoutData;

    /**
     * Create a new notification instance.
     */
    public function __construct(InvestmentHistory $investment, array $payoutData)
    {
        $this->investment = $investment;
        $this->payoutData = $payoutData;
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
        $cycle = $this->payoutData['cycle'];
        $isFinalCycle = $this->payoutData['is_final_cycle'];

        $subject = $isFinalCycle
            ? "🎉 Investment Completed - Final Payout Received!"
            : "💰 Investment Cycle $cycle Payout Received";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.investment_payout', [
                'user' => $notifiable,
                'investment' => $this->investment,
                'payoutData' => $this->payoutData,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isFinalCycle = $this->payoutData['is_final_cycle'];

        return [
            'type' => 'investment_payout',
            'title' => $isFinalCycle
                ? 'Investment Completed'
                : 'Investment Cycle Payout',
            'content' => $isFinalCycle
                ? "Your investment has completed all {$this->payoutData['total_cycles']} cycles. Total payout: $" . number_format($this->payoutData['payout_amount'], 2)
                : "Cycle {$this->payoutData['cycle']} of {$this->payoutData['total_cycles']} has matured. Payout: $" . number_format($this->payoutData['payout_amount'], 2),
            'investment_id' => $this->investment->id,
            'plan_name' => $this->investment->plan->name,
            'cycle' => $this->payoutData['cycle'],
            'total_cycles' => $this->payoutData['total_cycles'],
            'payout_amount' => $this->payoutData['payout_amount'],
            'interest' => $this->payoutData['interest'],
            'capital_returned' => $this->payoutData['capital_returned'],
            'is_final_cycle' => $isFinalCycle
        ];
    }
}
