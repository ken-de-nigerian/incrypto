<?php

namespace App\Notifications;

use App\Models\KycSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycApprovedNotification extends Notification
{
    use Queueable;

    public KycSubmission $kyc;

    /**
     * Create a new notification instance.
     */
    public function __construct(KycSubmission $kyc)
    {
        $this->kyc = $kyc;
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
            ->subject('Kyc Approved')
            ->view('emails.kyc.kyc_approved_confirmation', [
                'user' => $notifiable,
                'kyc' => $this->kyc,
            ]);
    }
}
