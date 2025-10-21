<?php

namespace App\Notifications;

use App\Models\KycSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycRejectedNotification extends Notification
{
    use Queueable;

    public KycSubmission $kyc;
    public array $data;

    /**
     * Create a new notification instance.
     */
    public function __construct(KycSubmission $kyc, array $data)
    {
        $this->kyc = $kyc;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kyc Rejected')
            ->view('emails.kyc.kyc_rejected_confirmation', [
                'user' => $notifiable,
                'kyc' => $this->kyc,
                'rejection_reason' => $this->data['rejection_reason'],
            ]);
    }
}
